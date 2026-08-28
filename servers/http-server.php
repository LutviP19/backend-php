<?php


declare(strict_types=1);

// // Disabled Log Errors
// ini_set('log_errors', 0);
// // ini_set('display_errors', 0);
// // ini_set('display_startup_errors', 0);
// error_reporting(~E_NOTICE & ~E_DEPRECATED & ~E_WARNING);

require_once __DIR__ . '/bootstrap.php';

use OpenSwoole\WebSocket\Server;
use OpenSwoole\Http\Request;
// use OpenSwoole\Http\Response;
use OpenSwoole\WebSocket\Frame;
use OpenSwoole\Server as TaskServer;
use OpenSwoole\Server\Task;
use App\Core\Support\App;
use App\Dispatchers\DynamicEventDispatcher;

\OpenSwoole\Runtime::enableCoroutine(true, SWOOLE_HOOK_ALL);

$serverip   = "127.0.0.1";
$serverport = 9501;

// Inisialisasi WebSocket Server (Secara native mendukung HTTP & Raw Socket)
$server = new Server($serverip, $serverport);

// Register instance server ke Container saat server startup
App::register(Server::class, $server);
// Menghindari Reflection Failure
App::register(\OpenSwoole\Http\Server::class, $server);
// Register events config
App::register('events', require BASEPATH . "/routes/events.php");

// Konfigurasi Server
$server->set([
    'worker_num'      => 2,       // Jumlah Worker Process untuk penanganan request
    'dispatch_mode'   => 2,       // Fixed dispatch mode
    'enable_coroutine' => true,    // Mengaktifkan Coroutine di dalam Event Loop

    'task_worker_num' => 4,       // Jumlah Task Worker untuk Async Event Task
    'task_enable_coroutine' => true,

    // Batas maksimal koneksi simultan (FD / File Descriptors)
    'max_connection' => 100, // Default Swoole biasanya 100.000 jika OS mendukung

    // =========================================================================
    // CONFIGURATION HEARTBEAT (Ping / Pong Detection)
    // =========================================================================

    // 1. Frekuensi pemeriksaan (dalam detik)
    // Server akan memindai seluruh koneksi setiap 30 detik sekali
    'heartbeat_check_interval' => 30,

    // 2. Batas toleransi tidak aktif (dalam detik)
    // Jika sebuah koneksi tidak mengirimkan data/ping sama sekali dalam 60 detik,
    // OpenSwoole akan menganggap koneksi mati dan MENUTUP-nya secara otomatis.
    'heartbeat_idle_time'      => 60,
]);

// =========================================================================
// HELPER QUEUE DISPATCHER (Fungsi untuk Memasukkan Job ke Queue)
// =========================================================================
function dispatchToQueue(Server $server, string $jobClass, array $payload, int $delaySeconds = 0): bool
{
    $queueData = [
        'type'      => 'queue_job',
        'job'       => $jobClass,
        'payload'   => $payload,
        'queued_at' => date('Y-m-d H:i:s')
    ];

    // Push ke Redis dari WebSocket Message
    $redis = setupRedisConnection();
    $redis->lpush('default_queue', json_encode([
        'job'     => $jobClass,
        'payload' => $payload
    ]));

    // Jika ada delay, gunakan OpenSwoole Timer (Delayed Queue)
    if ($delaySeconds > 0) {
        \OpenSwoole\Timer::after($delaySeconds * 1000, function () use ($server, $queueData) {
            $server->task($queueData);
        });
        return true;
    }

    // Push langsung ke Task Queue (Instant Queue)
    return $server->task($queueData) !== false;
}

// =========================================================================
// EVENT-DRIVEN SYSTEM (Penyalur Event / Event Dispatcher)
// =========================================================================
$eventListeners = [];

/**
 * Register Event Listener
 */
$on = function (string $eventName, callable $callback) use (&$eventListeners) {
    $eventListeners[$eventName][] = $callback;
};

/**
 * Trigger / Dispatch Event
 */
$dispatch = function (string $eventName, mixed $data = null) use (&$eventListeners, $server) {
    if (isset($eventListeners[$eventName])) {
        foreach ($eventListeners[$eventName] as $listener) {
            // Jalankan callback listener
            $listener($data, $server);
        }
    }
};

// =========================================================================
// REGISTRASI EVENT LISTENERS (Business Logic)
// =========================================================================
$on('user.connected', function ($fd, $server) {
    echo "[EVENT] User connected with FD: {$fd}\n";
});

$on('send.email', function ($payload, $server) {
    $fd    = $payload['fd'];
    $email = $payload['message']['email'] ?? 'user@example.com';

    echo "[EVENT] Menerima request kirim email ke {$email}. Mengirim ke Task Worker...\n";

    // Trigger Task Worker
    $taskId = $server->task([
        'action' => 'send_welcome_email',
        'fd'     => $fd,
        'email'  => $email,
    ]);

    // Respon cepat ke client
    $server->push($fd, json_encode([
        'type'    => 'task_queued',
        'message' => "Proses pengiriman email berjalan di background (Task ID: {$taskId})"
    ]));
});

$on('chat.message', function ($payload, $server) {
    $fd      = $payload['fd'];
    $message = $payload['message'];

    $server->push($fd, json_encode([
        'type'    => 'chat_response',
        'content' => "Server received: {$message}"
    ]));
});

// Event ketika client terputus
$on('user.disconnected', function ($fd, $server) {
    echo "[EVENT] User disconnected FD: {$fd}\n";

    // Bersihkan data session / state online user jika ada DI SINI
    // Example: Unset dari memory atau hapus mapping user_id -> fd
});

// =========================================================================
// OPENSWOOLE SOCKET EVENT HANDLERS
// =========================================================================

// 1. Event: Server Start
$server->on('Start', function (Server $server) use ($serverip, $serverport) {
    echo "========================================================\n";
    echo " OpenSwoole Event-Driven & Socket Server Running\n";
    echo " Listening on: ws://{$serverip}:{$serverport}\n";
    echo "========================================================\n";
});

// Helper dummy validasi
function validateToken(?string $token): bool
{
    // Implementasikan SDK Firebase JWT / Firebase\JWT\JWT::decode() Anda di sini
    return $token === 'SECRET_JWT_TOKEN_HERE';
}

function decodeJwtPayload(string $token): array
{
    return ['user_id' => 99]; // Return data user hasil decode
}

// HAPUS event 'handshake' lama, dan ganti event 'Open' menjadi seperti ini:
$server->on('Open', function (Server $server, Request $request) use ($dispatch) {
    $fd = $request->fd;

    // 1. Ambil Token dari Header atau Query String
    $token = null;

    if (isset($request->header['authorization'])) {
        $authHeader = $request->header['authorization'];
        $token = str_replace('Bearer ', '', $authHeader);
    } elseif (isset($request->get['token'])) {
        $token = $request->get['token'];
    }

    $clientIp = $request->server['remote_addr'] ?? '';

    // 2. Evaluasi Kriteria
    $isValidToken       = ($token && validateToken($token));
    $isIpTrusted        = in_array($clientIp, config('trusted_ips'));
    $currentConnections = count($server->connections);
    $maxCustomLimit     = 5000;

    // 3. REJECT KONEKSI (Jika tidak valid / limit terlampaui)
    if (!$isValidToken || !$isIpTrusted || $currentConnections >= $maxCustomLimit) {
        echo "[WS AUTH FAIL] Connection rejected for FD {$fd} (IP: {$clientIp})\n";

        // Putus koneksi WebSocket dengan Close Code 4001 (Unauthorized)
        $server->disconnect($fd, 4001, "Unauthorized or Limit Exceeded");
        return;
    }

    // 4. ACCEPT KONEKSI & DECODE USER
    $userData = decodeJwtPayload($token);

    echo "[WS AUTH SUCCESS] FD {$fd} authenticated as User #{$userData['user_id']}\n";

    // Trigger Custom Event
    if (is_callable($dispatch)) {
        $dispatch('user.connected', $request->fd);
    }
});

// 3. Event: Message Received (Terima Frame Socket)
$server->on('Message', function (Server $server, Frame $frame) use ($dispatch) {

    $fd   = $frame->fd;
    $data = $frame->data;

    // Decode JSON Payload dari Client
    $payload = json_decode($data, true);

    // 1. Tangani Ping-Pong WebSocket
    if (isset($payload['type']) && $payload['type'] === 'ping') {
        $server->push($fd, json_encode(['type' => 'pong']));
        return;
    }

    // 2. Validasi Format JSON dan ketersediaan key 'event'
    if (json_last_error() === JSON_ERROR_NONE && isset($payload['event'])) {
        $eventName = $payload['event'];
        $eventData = $payload['data'] ?? [];

        // --- KHUSUS EVENT process.invoice ---
        if ($eventName === 'process.invoice') {
            dispatchToQueue($server, '\App\Jobs\ProcessInvoiceJob', [
                'invoice_id' => $eventData['invoice_id'] ?? rand(1000, 9999),
                'user_id'    => 45,
                'fd'         => $fd
            ]);

            $server->push($fd, json_encode([
                'status'  => 'success',
                'message' => 'Job Invoice berhasil dimasukkan ke Queue!'
            ]));
            return;
        }

        // Dapatkan method secara DINAMIS dari payload JSON
        // Jika client tidak mengirim "method", otomatis generate dari nama event:
        // 'order.placed' -> 'onOrderPlaced'
        $defaultMethod = 'on' . str_replace(' ', '', ucwords(str_replace('.', ' ', $eventName)));
        $targetMethod  = $payload['method'] ?? $defaultMethod;

        // Ambil daftar event yang terdaftar dari config 'events'
        $registeredEvents = App::get('events') ?? [];

        // Cek apakah event terdaftar di routes/events.php
        if (isset($registeredEvents[$eventName])) {

            // Format nama class (contoh: 'order.placed' -> 'OrderPlacedEvent')
            $className  = str_replace(' ', '', ucwords(str_replace('.', ' ', $eventName))) . 'Event';
            $eventClass = "\\App\\Events\\" . $className;

            if (class_exists($eventClass)) {
                $eventInstance = new $eventClass($eventData);

                // Dispatch ke Task Queue secara Async
                $dispatcher = new DynamicEventDispatcher($server);
                $dispatcher->dispatchAsync($eventInstance, $targetMethod);

                $server->push($fd, json_encode([
                    'type'    => 'event_dispatched',
                    'message' => "Event [{$eventName}] diproses dengan method [{$targetMethod}] diproses di background task."
                ]));
                return;
            }

            // Error jika event terdaftar di config tapi class PHP-nya belum dibuat
            $server->push($fd, json_encode([
                'type'    => 'error',
                'message' => "Event class [{$eventClass}] tidak ditemukan."
            ]));
            return;
        }

        // Fallback ke Custom Closure Dispatcher lokal jika tidak ada di config
        $dispatch($eventName, [
            'fd'      => $fd,
            'message' => $eventData
        ]);
        return;
    }

    // if (($payload['event'] ?? '') === 'process.invoice') {
    //     // CONTOH 1: PUSH JOB KE QUEUE INSTANT
    //     dispatchToQueue($server, '\App\Jobs\ProcessInvoiceJob', [
    //         'invoice_id' => $payload['data']['invoice_id'] ?? rand(1000, 9999),
    //         'user_id'    => 45,
    //         'fd'         => $fd
    //     ]);

    //     // CONTOH 2: PUSH JOB KE QUEUE DENGAN DELAY (Misal: Kirim reminder 5 detik lagi)
    //     dispatchToQueue($server, '\App\Jobs\SendReminderJob', [
    //         'user_id' => 45,
    //         'fd'      => $fd
    //     ], delaySeconds: 5);

    //     $server->push($fd, json_encode([
    //         'status'  => 'success',
    //         'message' => 'Job Invoice & Reminder berhasil dimasukkan ke Queue!'
    //     ]));
    // }

    // // 3. Fallback jika payload bukan JSON valid atau tidak punya key 'event'
    // $server->push($fd, json_encode([
    //     'type'    => 'error',
    //     'message' => 'Format payload tidak valid. Wajib berupa JSON dan memiliki properti "event".'
    // ]));

    // Fallback jika pesan biasa (Raw text)
    $dispatch('chat.message', [
        'fd'      => $fd,
        'message' => $data
    ]);
});

// 4. Event: Async Task Handler (Dipanggil saat $server->task() dipicu)
$server->on('Task', function (TaskServer $server, Task $task) {

    // Debug Log
    if (function_exists('config') && config('app.debug')) {
        echo "[TASK Worker] Memproses Task #{$task->id} dari Worker #{$task->worker_id}\n";
    }

    $data = $task->data;

    // SKENARIO QUEUE JOB
    if (($data['type'] ?? '') === 'queue_job') {
        $jobClass = $data['job'] ?? '';
        $payload  = $data['payload'] ?? [];
        $clientFd = $payload['fd'] ?? ($data['fd'] ?? null); // Ambil FD secara presisi

        echo "[QUEUE EXECUTOR] Memproses Job: {$jobClass} (Task #{$task->id})\n";

        try {
            if (!empty($jobClass) && class_exists($jobClass)) {
                $jobInstance = new $jobClass($payload);
                if (method_exists($jobInstance, 'handle')) {
                    $jobInstance->handle();
                }
            } else {
                // Dummy fallback eksekusi
                \OpenSwoole\Coroutine::sleep(1);
                echo "[QUEUE EXECUTOR] Inline job executed for FD: {$clientFd}\n";
            }

            // Finish dan kirim FD ke event 'Finish'
            $task->finish([
                'status' => 'success',
                'job'    => $jobClass,
                'fd'     => $clientFd
            ]);
            return;
        } catch (\Throwable $e) {
            echo "[QUEUE ERROR] Job Failed: " . $e->getMessage() . "\n";
            $task->finish(['status' => 'failed', 'error' => $e->getMessage(), 'fd' => $clientFd]);
            return;
        }
    }

    // SKENARIO Dipicu oleh DynamicEventDispatcher
    if (is_array($data) && isset($data['listener'], $data['method'], $data['event'])) {
        try {
            DynamicEventDispatcher::executeListener(
                $data['listener'],
                $data['method'],
                $data['event']
            );
            $task->finish([
                'status' => 'success',
                'type'   => 'dynamic_event',
                'fd'     => $data['fd'] ?? null
            ]);
        } catch (\Throwable $e) {
            echo "[TASK ERROR] Failed executing listener {$data['listener']}: " . $e->getMessage() . PHP_EOL;
            $task->finish(['status' => 'error', 'message' => $e->getMessage()]);
        }
        return; // Hentikan eksekusi di sini
    }

    // SKENARIO Task Manual Berdasarkan 'action'
    $action = $data['action'] ?? null;

    switch ($action) {
        case 'send_welcome_email':
            \OpenSwoole\Coroutine::sleep(1);
            echo "[TASK Worker] Email sukses dikirim ke " . ($data['email'] ?? 'N/A') . "\n";

            $task->finish([
                'status' => 'success',
                'action' => 'send_welcome_email',
                'fd'     => $data['fd'] ?? null,
                'email'  => $data['email'] ?? null,
            ]);
            break;

        default:
            echo "[TASK WARNING] Unknown task action: " . json_encode($data) . PHP_EOL;
            $task->finish(['status' => 'ignored', 'message' => 'Unknown task action']);
            break;
    }
});

// 5. Event: Task Finish Handler (Dipanggil otomatis saat fungsi di event 'Task' me-return nilai)
$server->on('Finish', function (Server $server, int $taskId, mixed $data) {
    if (config('app.debug')) {
        echo "[TASK Finished] Task #{$taskId} selesai dieksekusi!\n";
    }

    // Jika ingin memberi notifikasi kembali ke client bahwa task background sudah selesai
    if (is_array($data) && !empty($data['fd']) && $server->isEstablished($data['fd'])) {
        $server->push($data['fd'], json_encode([
            'type'    => 'task_completed',
            // 'message' => "Email ke {$data['email']} telah sukses terkirim!"
            'message' => "Task #{$taskId} selesai dieksekusi."
        ]));
    }
});

// 6. Event: Connection Closed
$server->on('Close', function (Server $server, int $fd) use ($dispatch) {
    echo "[HEARTBEAT/CLOSE] Client FD {$fd} terputus atau dianggap mati oleh Heartbeat.\n";

    // Bersihkan data session / state online user jika ada
    // Example: Unset dari memory atau hapus mapping user_id -> fd

    // Trigger Custom Event
    $dispatch('user.disconnected', $fd);
});


// -------------------------------------------------------------------------
// REDIS PERSISTENT QUEUE CONSUMER (Opsional: Polling Job dari Redis)
// -------------------------------------------------------------------------
$server->on('WorkerStart', function (Server $server, int $workerId) {
    if ($workerId === 0 && !$server->taskworker) {
        \OpenSwoole\Coroutine::create(function () use ($server) {
            echo "[QUEUE WORKER] Listening to Redis Queue 'default_queue' (via Predis)...\n";

            try {
                // Inisialisasi Predis Client
                $redis = setupRedisConnection();

                while (true) {
                    // Predis: passing key 'default_queue' dan timeout 2 detik
                    $result = $redis->brpop('default_queue', 2);

                    // $result berisi: [0 => 'default_queue', 1 => '{"job":...}']
                    if (!empty($result) && isset($result[1])) {
                        $rawPayload = $result[1];
                        $jobData    = json_decode($rawPayload, true);

                        echo "[REDIS QUEUE] Job diterima dari Predis! Payload: {$rawPayload}\n";

                        // Oper pekerjaan dari Redis ke Swoole Task Queue
                        $server->task([
                            'type'    => 'queue_job',
                            'job'     => $jobData['job'] ?? null,
                            'payload' => $jobData['payload'] ?? []
                        ]);
                    }

                    // Berikan nafas sejenak pada coroutine Event Loop
                    \OpenSwoole\Coroutine::sleep(1);
                }
            } catch (\Throwable $e) {
                echo "[REDIS WORKER ERROR] " . $e->getMessage() . "\n";
            }
        });
    }
});

// Jalankan Server
$server->start();
