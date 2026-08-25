<?php 
declare(strict_types=1);

namespace Servers\Middleware\Websocket;

use OpenSwoole\Core\Psr\Response as ResponsePsr;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewareSetup implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        global $server;

        $serverParams = $request->getServerParams() ?? [];
        initializeServerConstant(array_merge($serverParams, $request->getHeaders() ?? []));

        if (config('app.debug')) {
            // var_dump('Middleware start clientIP:'.clientIP());
            echo "[" . date('Y-m-d H:i:s') . "] Middleware start clientIP:" .clientIP() . "\n";
        }
        // \App\Core\Support\Log::debug($_SERVER, 'ApiServer.MiddlewareSetup.process.$serverP');
        // \App\Core\Support\Log::debug(getallheaders(), 'ApiServer.MiddlewareSetup.process.getallheaders()');

        // // Check Status Server
        // $localIps = config('local_ips');
        // if (in_array(clientIP(), $localIps)
        //     && stripos($request->getUri()->getPath(), '/health') === 0) {

        //     return new ResponsePsr('Server running.', 200, '', ['Content-Type' => 'text/plain']);
        // }

        // // Metric Server Stats
        // if (in_array(clientIP(), $localIps)
        //     && stripos($request->getUri()->getPath(), '/metric') === 0) {

        //     // echo 'URI-Metric: '.$request->getUri()->getPath() . PHP_EOL;
        //     // memory leak example
        //     // global $c;
        //     // $c[] = new A();
        //     // Notice: add ACL rules and don't expose the metrics to the internet
        //     return new ResponsePsr($server->stats(\OPENSWOOLE_STATS_OPENMETRICS), 200, '', ['Content-Type' => 'text/plain']);
        // }

        // EnsureIpIsValid
        if (!in_array(clientIP(), config('trusted_ips'))) {
            return new ResponsePsr('Service Unavailable', 503, '', ['Content-Type' => 'text/plain']);
        }

        // // Validate Header
        // $headers = getallheaders();
        // $valid_headers = array_keys_exists(config('valid_headers'), $headers);
        // if (false === $valid_headers || ! isset($headers['X-Api-Token'])) {

        //     if (false === $valid_headers) {
        //         $statusCode = 500;
        //         $json = [
        //                     'status' => false,
        //                     'statusCode' => $statusCode,
        //                     'message' => 'Invalid header!',
        //                 ];
        //     }

        //     if (! isset($headers['X-Api-Token'])) {
        //         $statusCode = 403;
        //         $json = [
        //                     'status' => false,
        //                     'statusCode' => $statusCode,
        //                     'message' => 'Missing api token header!',
        //                 ];
        //     }

        //     if (config('app.debug')) {
        //         // var_dump('MiddlewareSetup failed. Invalid headers!');
        //         echo "[" . date('Y-m-d H:i:s') . "] [ERROR] MiddlewareSetup failed. Invalid headers!\n";
        //     }

        //     return new ResponsePsr(\json_encode($json), $statusCode, 'Missing credentials', ['Content-Type' => 'application/json']);
        // }

        // // Validate Api Token
        // if (matchEncryptedData(config('app.token_api'), $headers['X-Api-Token'][0]) === false) {
        //     $statusCode = 403;
        //     $json = [
        //                 'status' => false,
        //                 'statusCode' => $statusCode,
        //                 'message' => 'Invalid api token!',
        //             ];

        //     if (config('app.debug')) {
        //         // var_dump('MiddlewareSetup failed. Invalid API Token!');
        //         echo "[" . date('Y-m-d H:i:s') . "] [ERROR] MiddlewareSetup failed. Invalid API Token!\n";
        //     }

        //     return new ResponsePsr(\json_encode($json), $statusCode, '', ['Content-Type' => 'application/json']);
        // }

        // // Check Token Client Header
        // if (stripos($request->getUri()->getPath(), '/user') === 0 || stripos($request->getUri()->getPath(), '/api') === 0) {
        //     // echo "URI-Api: ". $request->getUri()->getPath() . PHP_EOL;

        //     $status = true;
        //     if (! isset($headers['X-Client-Token'])) {
        //         $status = false;
        //         $statusCode = 403;
        //         $json = [
        //                     'status' => false,
        //                     'statusCode' => $statusCode,
        //                     'message' => 'Missing client token header!',
        //                 ];
        //     }

        //     if (false === $status) {
        //         if (config('app.debug')) {
        //             // var_dump('MiddlewareSetup failed. Invalid Client Token!');
        //             echo "[" . date('Y-m-d H:i:s') . "] [ERROR] MiddlewareSetup failed. Invalid Client Token!\n";
        //         }

        //         return new ResponsePsr(\json_encode($json), $statusCode, 'Missing credentials', ['Content-Type' => 'application/json']);
        //     }

        // }

        $response = $handler->handle($request);

        if (is_null($response)) {
            return new ResponsePsr(
                json_encode(['status' => false, 'message' => 'Internal Server Error: Null Response']),
                500,
                'Internal Error',
                ['Content-Type' => 'application/json']
            );
        }

        if (config('app.debug')) {
            // var_dump('MiddlewareSetup passed');
            echo "[" . date('Y-m-d H:i:s') . "] [OK] MiddlewareSetup passed\n";
        }

        return $response;
    }
}