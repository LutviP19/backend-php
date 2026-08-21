<?php

use OpenSwoole\WebSocket\Server;
use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;
use OpenSwoole\WebSocket\Frame;
use OpenSwoole\Server as TaskServer;
use OpenSwoole\Server\Task;

$serverip   = "0.0.0.0";
$serverport = 9502;

// 1. Inisialisasi WebSocket Server (Secara native mendukung HTTP & Raw Socket)
$server = new Server($serverip, $serverport);

// 2. Konfigurasi Server
$server->set([
    'worker_num'      => 2,       // Jumlah Worker Process untuk penanganan request
    'task_worker_num' => 4,       // Jumlah Task Worker untuk Async Event Task
    'dispatch_mode'   => 2,       // Fixed dispatch mode
    'enable_coroutine' => true,    // Mengaktifkan Coroutine di dalam Event Loop
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
// 2. REGISTRASI EVENT LISTENERS (Business Logic)
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

// // Register event Handshake
// $server->on('Handshake', function (Request $request, Response $response) use ($server) {
//     // 1. Ambil Kriteria (Contoh: Token dari Query Parameter atau Header)
//     $token = $request->get['token'] ?? $request->header['sec-websocket-protocol'] ?? null;
//     $clientIp = $request->server['remote_addr'] ?? '';

//     // 2. Evaluasi Kriteria
//     $isValidToken = ($token === 'secret-token-123'); // Contoh cek token
//     $isIpBlocked  = ($clientIp === '192.168.1.100');   // Contoh blacklist IP

//     // Contoh Cek Kuota: Tolak jika koneksi aktif sudah melebihi limit custom Anda
//     $currentConnections = count($server->connections);
//     $maxCustomLimit = 5000;

//     if (!$isValidToken || $isIpBlocked || $currentConnections >= $maxCustomLimit) {
//         // 3. REJECT KONEKSI
//         // Kirim response HTTP error
//         $response->status(401);
//         $response->header('Content-Type', 'text/plain');
//         $response->end("WebSocket Connection Rejected: Unauthorized or Limit Exceeded.");

//         // Return false menandakan Handshake GAGAL
//         return false;
//     }

//     // 4. ACCEPT KONEKSI (Proses Handshake Manual jika lolos kriteria)
//     $secWebSocketKey = $request->header['sec-websocket-key'];
//     if (preg_match('#^[+/0-9A-Za-z]{21}[AQgw]==$#', $secWebSocketKey) === 0 || strlen($secWebSocketKey) !== 24) {
//         $response->status(400);
//         $response->end();
//         return false;
//     }

//     // Buat Sec-WebSocket-Accept key sesuai spesifikasi RFC 6455
//     $key = base64_encode(
//         sha1($secWebSocketKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true)
//     );

//     $headers = [
//         'Upgrade'               => 'websocket',
//         'Connection'            => 'Upgrade',
//         'Sec-WebSocket-Accept'  => $key,
//         'Sec-WebSocket-Version' => '13',
//     ];

//     if (isset($request->header['sec-websocket-protocol'])) {
//         $headers['Sec-WebSocket-Protocol'] = $request->header['sec-websocket-protocol'];
//     }

//     foreach ($headers as $key => $val) {
//         $response->header($key, $val);
//     }

//     $response->status(101); // 101 Switching Protocols
//     $response->end();

//     // Trigger event Open secara manual setelah Handshake sukses
//     $server->defer(function () use ($server, $request) {
//         $server->trigger('Open', [$server, $request]);
//     });

//     return true;
// });

// 2. Event: Connection Established (Handshake sukses)
$server->on('Open', function (Server $server, Request $request) use ($dispatch) {
    // Trigger Custom Event
    $dispatch('user.connected', $request->fd);
});

// 3. Event: Message Received (Terima Frame Socket)
$server->on('Message', function (Server $server, Frame $frame) use ($dispatch) {

    // Tangani Custom Ping dari Client
    $dataPing = json_decode($frame->data, true);
    if (isset($dataPing['type']) && $dataPing['type'] === 'ping') {
        // Balas dengan pong
        $server->push($frame->fd, json_encode(['type' => 'pong']));
        return; // Mengirim data ini otomatis memperbarui 'last_time' di Swoole
    }

    $fd   = $frame->fd;
    $data = $frame->data;

    // Decode JSON Payload dari Client
    $payload = json_decode($data, true);

    if (json_last_error() === JSON_ERROR_NONE && isset($payload['event'])) {
        // Jika format JSON valid dan mengandung nama 'event', lempar ke Event Dispatcher
        $dispatch($payload['event'], [
            'fd'      => $fd,
            'message' => $payload['data'] ?? null
        ]);
    } else {
        // Fallback jika pesan biasa (Raw text)
        $dispatch('chat.message', [
            'fd'      => $fd,
            'message' => $data
        ]);
    }
});

// 4. Event: Async Task Handler (Dipanggil saat $server->task() dipicu)
$server->on('Task', function (TaskServer $server, Task $task) {
    // Properti bawaan dari objek Task:
    $taskId      = $task->id;        // ID Task (int)
    $srcWorkerId = $task->worker_id; // ID Worker pengirim (int)
    $data        = $task->data;      // Data/payload yang dikirim dari $server->task()

    echo "[TASK Worker] Memproses Task #{$taskId} dari Worker #{$srcWorkerId}\n";

    if (($data['action'] ?? '') === 'send_welcome_email') {
        \OpenSwoole\Coroutine::sleep(3); // Sekarang Coroutine::sleep aman dipakai!
        echo "[TASK Worker] Email sukses dikirim ke {$data['email']}\n";
    }

    // Untuk mengembalikan data ke event 'Finish', gunakan $task->finish()
    $task->finish([
        'status' => 'success',
        'fd'     => $data['fd'] ?? null,
        'email'  => $data['email'] ?? null,
    ]);
});

// 5. Event: Task Finish Handler (Dipanggil otomatis saat fungsi di event 'Task' me-return nilai)
$server->on('Finish', function (Server $server, int $taskId, mixed $data) {
    echo "[TASK Finished] Task #{$taskId} selesai dieksekusi!\n";

    // Jika ingin memberi notifikasi kembali ke client bahwa task background sudah selesai
    if (is_array($data) && !empty($data['fd']) && $server->isEstablished($data['fd'])) {
        $server->push($data['fd'], json_encode([
            'type'    => 'task_completed',
            'message' => "Email ke {$data['email']} telah sukses terkirim!"
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

// Jalankan Server
$server->start();
