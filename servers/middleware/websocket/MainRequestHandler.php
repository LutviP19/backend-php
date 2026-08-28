<?php 
declare(strict_types=1);

namespace Servers\Middleware\Websocket;

use OpenSwoole\Core\Psr\Response as ResponsePsr;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use OpenSwoole\Http\Server;
use OpenSwoole\Coroutine;

class MainRequestHandler implements MiddlewareInterface
{
    private Server $server;
    private array $serverStats;

    public function __construct(Server $server, array &$serverStats)
    {
        $this->server = $server;
        $this->serverStats = &$serverStats;
    }

    // Gunakan method process() dari MiddlewareInterface
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->serverStats['requests_total']++;
        $uri = $request->getUri()->getPath();

        if ($uri === '/extra') {
            $orderData = ['id' => 123, 'items' => ['item1', 'item2']];
            $eventInstance = new \App\Events\OrderPlacedEvent($orderData);

            // Gunakan DynamicEventDispatcher untuk melempar event ke Task Queue otomatis
            $dispatcher = new \App\Dispatchers\DynamicEventDispatcher($this->server);
            $dispatcher->dispatchAsync($eventInstance, 'onOrderPlaced');

            return new ResponsePsr(
                json_encode([
                    'status'  => 'success',
                    'message' => 'Task Event-onOrderPlaced triggered successfully via OpenSwoole Task.'
                ]),
                200,
                'OK',
                ['Content-Type' => 'application/json']
            );
        }

        // ---------------------------------------------------------------------
        // ROUTE A: Health Check API (/health)
        // ---------------------------------------------------------------------
        if ($uri === '/health') {
            $dbHealth = checkDatabaseHealth();
            $isHealthy = ($dbHealth['status'] === 'UP');
            $statusCode = $isHealthy ? 200 : 503;

            $serverParams = $request->getServerParams();
            $clientIp = $serverParams['remote_addr'] ?? ($request->getHeaderLine('X-Forwarded-For') ?: '127.0.0.1');

            $body = json_encode([
                'status'     => $isHealthy ? 'OK' : 'DEGRADED',
                'timestamp'  => date('Y-m-d H:i:s'),
                'uptime'     => (time() - $this->serverStats['start_time']) . 's',
                'components' => [
                    'client_ip' => $clientIp,
                    'database'  => $dbHealth,
                    'memory'    => [
                        'usage_bytes'     => memory_get_usage(true),
                        'usage_formatted' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB'
                    ]
                ]
            ], JSON_PRETTY_PRINT);

            return new ResponsePsr(
                $body,
                $statusCode,
                'OK',
                ['Content-Type' => 'application/json']
            );
        }

        // ---------------------------------------------------------------------
        // ROUTE B: Prometheus Metrics Endpoint (/metrics)
        // ---------------------------------------------------------------------
        if ($uri === '/metrics') {
            $metricsOutput = $this->server->stats(\OPENSWOOLE_STATS_OPENMETRICS);

            return new ResponsePsr(
                $metricsOutput,
                200,
                'OK',
                ['Content-Type' => 'text/plain; version=0.0.4']
            );
        }

        // ---------------------------------------------------------------------
        // ROUTE C: Server-Sent Events / SSE (/sse/realtime-monitor)
        // ---------------------------------------------------------------------
        if ($uri === '/sse/realtime-monitor') {

            // ===== SSE Middleware
            
            // 1. Ambil header Origin dari Request
            $origin = $request->getHeaderLine('Origin');

            // 2. Jika Origin ada (Request dari Browser), lakukan validasi
            if (!empty($origin)) {
                // Extract host dari Origin (misal "http://127.0.0.1:9502" -> "127.0.0.1")
                $originHost = parse_url($origin, PHP_URL_HOST);
                $allowedOrigins = config('trusted_ips');

                // Cek apakah full origin atau host-nya ada di allowed list
                $isAllowed = in_array($origin, $allowedOrigins, true) || in_array($originHost, $allowedOrigins, true);

                if (!$isAllowed) {
                    return new ResponsePsr(
                        'Forbidden Origin',
                        403,
                        'Forbidden',
                        ['Content-Type' => 'text/plain']
                    );
                }
            }

            // Set CORS header secara dinamis (fallback ke '*' jika non-browser)
            $corsOrigin = !empty($origin) ? $origin : '*';

            // 1. Ambil ticket dari Query Param
            $queryParams = $request->getQueryParams();
            $ticket = $queryParams['ticket'] ?? null;

            // 2. Validasi Ticket (Cek Redis / Memory Store)
            if (!$ticket || !$this->validateAndConsumeTicket($ticket)) {
                return new ResponsePsr(
                    json_encode(['error' => 'Unauthorized or expired ticket']),
                    401,
                    'Unauthorized',
                    ['Content-Type' => 'application/json']
                );
            }

            // ===== End SSE Middleware

            // 1. Buat temporary memory stream
            $resource = fopen('php://temp', 'r+');
            
            // 2. Isi stream dengan chunk pertama agar langsung ada byte terkirim (mencegah timeout Insomnia)
            $dbCheck = checkDatabaseHealth();
            $initialData = [
                'time'        => date('H:i:s'),
                'cpu_load'    => sys_getloadavg()[0] ?? 0,
                'memory_mb'   => round(memory_get_usage(true) / 1024 / 1024, 2),
                'db_latency'  => $dbCheck['latency'] ?? 'N/A',
                'active_cors' => Coroutine::stats()['coroutine_num'],
            ];

            $chunk = "event: metrics_update\ndata: " . json_encode($initialData) . "\n\n";
            fwrite($resource, $chunk);
            rewind($resource); // Kembalikan pointer ke awal stream untuk dibaca oleh OpenSwoole

            // 3. Masukkan resource yang valid ke Stream PSR
            $stream = new \OpenSwoole\Core\Psr\Stream($resource);            

            return new ResponsePsr(
                $stream,
                200,
                'OK',
                [
                    'Content-Type'                => 'text/event-stream',
                    'Cache-Control'               => 'no-cache',
                    'Connection'                  => 'keep-alive',
                    'X-Accel-Buffering'           => 'no',
                    'Access-Control-Allow-Origin' => $corsOrigin, // Hanya izinkan domain yang valid
                ]
            );
        }

        // ---------------------------------------------------------------------
        // DEFAULT: 404 Route
        // ---------------------------------------------------------------------
        return new ResponsePsr(
            json_encode(['error' => 'Endpoint Not Found']),
            404,
            'Not Found',
            ['Content-Type' => 'application/json']
        );
    }

    private function validateAndConsumeTicket(string $ticket): bool 
    {
        // Cek keberadaan ticket di Cache/Redis, lalu langsung HAPUS agar tidak bisa dipakai 2x
        // return Redis::del("sse_ticket:{$ticket}") > 0;
        return true; // Contoh logika sederhana
    }
}