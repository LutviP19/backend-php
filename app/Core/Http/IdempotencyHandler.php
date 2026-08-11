<?php

namespace App\Core\Http;

use App\Core\Security\IdempotencyManager;

class IdempotencyHandler
{
    /**
     * Pengecekan idempotency ringkas untuk digunakan langsung di controller/action.
     *
     * @param mixed $request Request object (Swoole Request, Framework Request, atau null)
     * @return array|null Mengembalikan array response error jika conflict/replay, atau NULL jika aman dilanjutkan.
     */
    public static function simpleCheck($request = null): ?array
    {
        // 1. Ekstraksi Header X-Idempotency-Key
        $idempotencyKey = self::extractIdempotencyKey($request);

        // Jika tidak ada header Idempotency Key, request dianggap transaksi biasa
        if (empty($idempotencyKey)) {
            return null;
        }

        // 2. Cek status idempotency yang tersimpan
        $check = IdempotencyManager::check($idempotencyKey);

        // Kasus A: Request sedang diproses (Prevent Double Submit)
        if ($check['status'] === 'PROCESSING') {
            return [
                'status'  => false,
                'message' => 'Permintaan Anda sedang diproses. Mohon tunggu sejenak.'
            ];
        }

        // Kasus B: Request SUDAH selesai diproses sebelumnya (Replay Attempt)
        if ($check['status'] === 'COMPLETED') {
            return [
                'status'   => false,
                'message'  => 'Transaksi/Permintaan ini sudah pernah berhasil diproses.',
                'cached'   => true,
                'response' => $check['response'] ?? null
            ];
        }

        // 3. Coba kunci (Lock) untuk request baru ini (TTL 60 detik)
        if (!IdempotencyManager::lock($idempotencyKey, 60)) {
            return [
                'status'  => false,
                'message' => 'Permintaan duplikat terdeteksi.'
            ];
        }

        // Kembalikan NULL artinya request aman diproses oleh controller
        return null;
    }

    /**
     * Handler Wrapper / Middleware-like execution
     */
    public static function handle($request, $response, callable $controllerLogic)
    {
        $isSwoole = function_exists('isSwoole') && \isSwoole();

        // 1. Ekstraksi Header X-Idempotency-Key
        $idempotencyKey = self::extractIdempotencyKey($request);

        // Jika tidak ada header Idempotency Key, eksekusi controller langsung
        if (empty($idempotencyKey)) {
            return $controllerLogic();
        }

        // 2. Cek status idempotency
        $check = IdempotencyManager::check($idempotencyKey);

        // Jika request sedang diproses (Double Submit)
        if ($check['status'] === 'PROCESSING') {
            return self::sendResponse($response, $isSwoole, 429, json_encode([
                'status'  => false,
                'message' => 'Permintaan Anda sedang diproses. Mohon tunggu...'
            ]), ['Content-Type' => 'application/json']);
        }

        // Jika request SUDAH pernah diproses, kembalikan cached response (Replay)
        if ($check['status'] === 'COMPLETED') {
            return self::sendResponse(
                $response, 
                $isSwoole, 
                $check['http_code'] ?: 200, 
                $check['response'], 
                [
                    'Content-Type'       => 'application/json',
                    'X-Cache-Idempotent' => 'true'
                ]
            );
        }

        // 3. Coba kunci request baru
        if (!IdempotencyManager::lock($idempotencyKey, 60)) {
            return self::sendResponse($response, $isSwoole, 429, json_encode([
                'status'  => false,
                'message' => 'Permintaan duplikat terdeteksi.'
            ]), ['Content-Type' => 'application/json']);
        }

        // 4. Eksekusi Controller Logic
        try {
            $result = $controllerLogic();

            // Dapatkan HTTP Status Code
            $httpCode = 200;
            if ($isSwoole && is_object($response) && method_exists($response, 'getStatusCode')) {
                $httpCode = $response->getStatusCode() ?: 200;
            } else {
                $httpCode = http_response_code() ?: 200;
            }

            // Normalisasi output ke string untuk dikirim ke cache
            $responseBody = is_array($result) || is_object($result) 
                ? json_encode($result) 
                : (string) $result;

            // Simpan response ke cache (TTL 300 detik / 5 menit)
            IdempotencyManager::saveResponse($idempotencyKey, $responseBody, $httpCode, 300);

            return $result;

        } catch (\Throwable $e) {
            // Hapus lock jika terjadi error agar user dapat mengulang request
            IdempotencyManager::unlock($idempotencyKey);
            throw $e;
        }
    }

    /**
     * Helper membaca header X-Idempotency-Key dari berbagai jenis Request object / server global
     */
    public static function extractIdempotencyKey($request = null): ?string
    {
        // 1. Jika $request adalah Object (Swoole Request atau Framework Request)
        if (is_object($request)) {
            // Swoole Http Request ($request->header)
            if (isset($request->header) && is_array($request->header)) {
                $headers = array_change_key_case($request->header, CASE_LOWER);
                return $headers['x-idempotency-key'] ?? null;
            }

            // Framework Request Object ($request->header('X-Idempotency-Key'))
            if (method_exists($request, 'header')) {
                return $request->header('X-Idempotency-Key') ?? $request->header('x-idempotency-key');
            }
        }

        // 2. Native PHP Global ($_SERVER)
        if (isset($_SERVER['HTTP_X_IDEMPOTENCY_KEY'])) {
            return $_SERVER['HTTP_X_IDEMPOTENCY_KEY'];
        }

        // 3. Fallback getallheaders() untuk PHP-FPM / Apache / Nginx
        if (function_exists('getallheaders')) {
            $headers = array_change_key_case(getallheaders() ?: [], CASE_LOWER);
            return $headers['x-idempotency-key'] ?? null;
        }

        return null;
    }

    /**
     * Helper mengirim respons HTTP secara aman (Swoole vs PHP-FPM)
     */
    private static function sendResponse($response, bool $isSwoole, int $statusCode, string $body, array $headers = [])
    {
        if ($isSwoole && is_object($response) && method_exists($response, 'end')) {
            $response->status($statusCode);
            foreach ($headers as $key => $val) {
                $response->header($key, $val);
            }
            return $response->end($body);
        }

        // PHP-FPM Fallback
        http_response_code($statusCode);
        foreach ($headers as $key => $val) {
            header("{$key}: {$val}");
        }
        echo $body;
        return $body;
    }
}