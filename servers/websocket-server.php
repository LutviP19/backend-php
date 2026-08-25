<?php

declare(strict_types=1);

// // Disabled Log Errors
// ini_set('log_errors', 0);
// // ini_set('display_errors', 0);
// // ini_set('display_startup_errors', 0);
// error_reporting(~E_NOTICE & ~E_DEPRECATED & ~E_WARNING);

require_once __DIR__ . '/bootstrap.php';

// Load All Middleware
$apiMiddlewarePath = BASEPATH . '/servers/middleware/websocket/*.php';
foreach (glob($apiMiddlewarePath) as $filePath) {
    if (is_file($filePath)) {
        require_once $filePath;
    }
}

use Servers\Middleware\Websocket\{MiddlewareSetup, MainRequestHandler};
use OpenSwoole\Core\Psr\Middleware\StackHandler;
use OpenSwoole\Http\Server;
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

// Task Handler untuk Heavy Cron Job
$server->on('Task', function (Server $server, \OpenSwoole\Server\Task $task) {
    $data = $task->data;
    if (($data['action'] ?? '') === 'cron_maintenance') {
        Coroutine::sleep(2); // Heavy processing
        // Run included php file
        require_once BASEPATH . '/cron/cleanTmp.php';
        echo "[TASK] Maintenance cron finished successfully.\n";
    }
    $task->finish(['status' => 'done']);
});


// =========================================================================
// REGISTER KE STACK HANDLER SERVER
// =========================================================================
$stack = (new StackHandler())
    ->add(new MainRequestHandler($server, $serverStats))
    ->add(new MiddlewareSetup());

$server->setHandler($stack);

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

// PENTING: Bersihkan Pool ketika Worker Berhenti (Worker Stop/Reload)
$server->on('WorkerStop', function ($server, int $workerId) {
    // if (class_exists('DatabasePoolManager') && method_exists('DatabasePoolManager', 'close')) {
    //     DatabasePoolManager::close();
    // }
    echo "[" . date('Y-m-d H:i:s') . "] [INFO] Worker #{$workerId} stopped and pool cleaned up.\n";
});

$server->start();
