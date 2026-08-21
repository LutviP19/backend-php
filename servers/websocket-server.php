<?php

declare(strict_types=1);

// // Disabled Log Errors
// ini_set('log_errors', 0);
// // ini_set('display_errors', 0);
// // ini_set('display_startup_errors', 0);
// error_reporting(~E_NOTICE & ~E_DEPRECATED & ~E_WARNING);

require_once __DIR__ . '/bootstrap.php';

use OpenSwoole\Http\Server;
use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;
use OpenSwoole\Timer;
use OpenSwoole\Coroutine;

$serverip   = "0.0.0.0";
$serverport = 9502;

$server = new Server($serverip, $serverport);

$server->set([
    'worker_num'            => 2,
    'task_worker_num'       => 2,
    'enable_coroutine'      => true,
    'task_enable_coroutine' => true,

    'open_cpu_affinity'     => true, // Opsional: untuk metrik CPU
]);

// Memory Storage sederhana untuk melacak statistik server
$serverStats = [
    'requests_total' => 0,
    'errors_total'   => 0,
    'start_time'     => time(),
];



// =========================================================================
// CRONJOB HANDLER / SCHEDULER (OpenSwoole Timer)
// =========================================================================
$server->on('WorkerStart', function (Server $server, int $workerId) {
    // Jalankan Scheduler HANYA di Worker 0 agar job tidak tereksekusi ganda
    if ($workerId === 0) {
        echo "[CRON] CronJob Scheduler initialized on Worker #0\n";

        // CronJob 1: Eksekusi Setiap 10 Detik (Health Check DB & System)
        Timer::tick(10000, function () {
            $dbStatus = checkDatabaseHealth();
            $mem = round(memory_get_usage(true) / 1024 / 1024, 2);
            echo "[CRON][10s Check] DB Status: {$dbStatus['status']} | Mem Usage: {$mem} MB\n";
        });

        // CronJob 2: Eksekusi Setiap 1 Menit (Pembersihan Logs / Sync Background)
        Timer::tick(60000, function () use ($server) {
            echo "[CRON][1m Job] Executing background maintenance task...\n";
            $server->task(['action' => 'cron_maintenance']);
        });
    }
});

// Task Handler untuk Heavy Cron Job
$server->on('Task', function (Server $server, \OpenSwoole\Server\Task $task) {
    $data = $task->data;
    if (($data['action'] ?? '') === 'cron_maintenance') {
        Coroutine::sleep(2); // Heavy processing
        echo "[TASK] Maintenance cron finished successfully.\n";
    }
    $task->finish(['status' => 'done']);
});

// =========================================================================
// HTTP ROUTER (Metrics, Health, SSE, Monitoring API)
// =========================================================================
$server->on('Request', function (Request $request, Response $response) use ($server, &$serverStats) {
    $serverStats['requests_total']++;
    $uri = $request->server['request_uri'];

    // ---------------------------------------------------------------------
    // ROUTE A: Health Check API (/health)
    // ---------------------------------------------------------------------
    if ($uri === '/health') {
        $dbHealth = checkDatabaseHealth();
        $isHealthy = ($dbHealth['status'] === 'UP');

        $response->header('Content-Type', 'application/json');
        $response->status($isHealthy ? 200 : 503);
        $response->end(json_encode([
            'status'     => $isHealthy ? 'OK' : 'DEGRADED',
            'timestamp'  => date('Y-m-d H:i:s'),
            'uptime'     => (time() - $serverStats['start_time']) . 's',
            'components' => [
                'database' => $dbHealth,
                'memory'   => [
                    'usage_bytes' => memory_get_usage(true),
                    'usage_formatted' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB'
                ]
            ]
        ], JSON_PRETTY_PRINT));
        return;
    }

    // ---------------------------------------------------------------------
    // ROUTE B: Prometheus Metrics Endpoint (/metrics)
    // ---------------------------------------------------------------------
    if ($uri === '/metrics') {
        // // Ambil statistik mendalam dari OpenSwoole Core Engine
        // $stats = $server->stats();

        // // Format ringkas bawaan Prometheus OpenMetrics
        // $output = [
        //     '# HELP openswoole_connection_num Total active TCP/WebSocket connections',
        //     '# TYPE openswoole_connection_num gauge',
        //     'openswoole_connection_num ' . ($stats['connection_num'] ?? 0),
        //     '',
        //     '# HELP openswoole_request_count Total HTTP requests handled',
        //     '# TYPE openswoole_request_count counter',
        //     'openswoole_request_count ' . ($stats['request_count'] ?? 0),
        //     '',
        //     '# HELP openswoole_coroutine_num Current active coroutines',
        //     '# TYPE openswoole_coroutine_num gauge',
        //     'openswoole_coroutine_num ' . (\OpenSwoole\Coroutine::stats()['coroutine_num'] ?? 0),
        //     '',
        //     '# HELP openswoole_task_queue_num Number of tasks waiting in queue',
        //     '# TYPE openswoole_task_queue_num gauge',
        //     'openswoole_task_queue_num ' . ($stats['task_queue_num'] ?? 0),
        //     '',
        //     '# HELP openswoole_worker_num Worker process count',
        //     '# TYPE openswoole_worker_num gauge',
        //     'openswoole_worker_num ' . $server->setting['worker_num'],
        //     ''
        // ];

        // $response->header('Content-Type', 'text/plain; version=0.0.4');
        // $response->end(implode("\n", $output));
        // return;

        // Memanggil statistik native OpenSwoole berformat OpenMetrics / Prometheus
        $metricsOutput = $server->stats(\OPENSWOOLE_STATS_OPENMETRICS);

        $response->header('Content-Type', 'text/plain; version=0.0.4');
        $response->end($metricsOutput);
        return;
    }

    // ---------------------------------------------------------------------
    // ROUTE C: Server-Sent Events / SSE (/sse/realtime-monitor)
    // ---------------------------------------------------------------------
    if ($uri === '/sse/realtime-monitor') {
        $response->header('Content-Type', 'text/event-stream');
        $response->header('Cache-Control', 'no-cache');
        $response->header('Connection', 'keep-alive');
        $response->header('Access-Control-Allow-Origin', '*');

        // Loop Streaming SSE Realtime Data ke Client (Setiap 2 Detik)
        $count = 0;
        while (true) {
            $dbCheck = checkDatabaseHealth();
            $data = [
                'time'        => date('H:i:s'),
                'cpu_load'    => sys_getloadavg()[0] ?? 0,
                'memory_mb'   => round(memory_get_usage(true) / 1024 / 1024, 2),
                'db_latency'  => $dbCheck['latency'] ?? 'N/A',
                'active_cors' => Coroutine::stats()['coroutine_num'],
            ];

            // Format Wajib SSE: "data: {JSON}\n\n"
            $written = $response->write("event: metrics_update\ndata: " . json_encode($data) . "\n\n");

            // Jika koneksi client terputus, break loop SSE
            if (!$written) {
                break;
            }

            // Yield/Sleep 2 detik tanpa blocking I/O (Coroutine Context)
            Coroutine::sleep(2);
            $count++;

            // Batasi max streaming per koneksi (misal 300 siklus ~ 10 menit), lalu biarkan reconnect
            if ($count >= 300) {
                break;
            }
        }
        $response->end();
        return;
    }

    // Default 404 Route
    $response->status(404);
    $response->end(json_encode(['error' => 'Endpoint Not Found']));
});

// =========================================================================
// SERVER START HANDLER
// =========================================================================
$server->on('Start', function (Server $server) use ($serverip, $serverport) {
    echo "========================================================\n";
    echo " OpenSwoole System & Monitoring Server Running\n";
    echo " Listening on : http://{$serverip}:{$serverport}\n";
    echo " Endpoints:\n";
    echo "   - Health Check API : http://127.0.0.1:{$serverport}/health\n";
    echo "   - Prometheus Metrics: http://127.0.0.1:{$serverport}/metrics\n";
    echo "   - Realtime SSE Stream: http://127.0.0.1:{$serverport}/sse/realtime-monitor\n";
    echo "========================================================\n";
});

// =========================================================================
// EVENT FINISH (Wajib ada jika task_worker_num > 0)
// =========================================================================
$server->on('Finish', function (OpenSwoole\Server $server, int $taskId, mixed $data) {
    // Callback ini dipanggil saat Task Worker selesai mengeksekusi tugas
    // $data adalah nilai yang dikirim dari $task->finish($data) atau return $data

    // Anda bisa mengosongkannya jika tidak ada aksional khusus setelah task selesai
    echo "[TASK FINISHED] Task #{$taskId} execution completed.\n";
});


$server->start();
