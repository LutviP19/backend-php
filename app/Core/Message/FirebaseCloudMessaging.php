<?php

namespace App\Core\Message;

class FirebaseCloudMessaging
{
    protected $config_path = BASEPATH . '/config/service-account.json';
    protected $config;


    public function __construct($config_path = null)
    {
        $this->config_path = $config_path ?: $this->config_path;

        if (! \file_exists($this->config_path)) {
            throw new Exception("File service-account Not Exists.");

            \App\Core\Support\Log::error([
                'message' => "File service-account.json Not Exists."
            ], 'FirebaseCloudMessaging.__construct.file_get_contents($config_path)');

            return;
        }

        $this->config = json_decode(file_get_contents($this->config_path), true);
    }

    public function createAccessToken()
    {
        $sa = json_decode(file_get_contents($this->config_path), true);
        if (!$sa || !isset($sa['client_email'], $sa['private_key'])) {
            // http_response_code(500);
            // die("Invalid service-account.json");

            \App\Core\Support\Log::error([
                'message' => "Invalid service-account.json"
            ], 'FirebaseCloudMessaging.createAccessToken.file_get_contents($sa)');

            return null;
        }
        $header = b64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $now = time();
        $claim = b64url(json_encode([
          'iss'   => $sa['client_email'],
          'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
          'aud'   => 'https://oauth2.googleapis.com/token',
          'iat'   => $now,
          'exp'   => $now + 3600
        ]));

        $input = $header . '.' . $claim;
        $signature = '';
        openssl_sign($input, $signature, $sa['private_key'], 'SHA256');
        $jwt = $input . '.' . b64url($signature);

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
          CURLOPT_POST => true,
          CURLOPT_POSTFIELDS => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt
          ]),
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_TIMEOUT => 20
        ]);


        $res = curl_exec($ch);
        if ($res === false) {
            $err = curl_error($ch);
            curl_close($ch);
            // http_response_code(500);
            // die("OAuth token error: $err");

            \App\Core\Support\Log::error([
                'message' => "OAuth token failed: $err"
            ], 'FirebaseCloudMessaging.createAccessToken.curl($res)');

            return null;
        }
        curl_close($ch);


        $json = json_decode($res, true);
        if (!isset($json['access_token'])) {
            // http_response_code(500);
            // die("OAuth token failed: $res");

            \App\Core\Support\Log::error([
                'message' => "OAuth token failed: $res"
            ], 'FirebaseCloudMessaging.createAccessToken.$json');

            return null;
        }

        $accessToken = $json['access_token'] ?: null;

        // \App\Core\Support\Log::debug($accessToken, 'FirebaseCloudMessaging.createAccessToken.accessToken');
        return $accessToken;
    }

    public function sendMessage($accessToken, $token, $title, $body, $icon = null)
    {
        $accessToken = $accessToken ?: $this->createAccessToken();

        if (\is_null($accessToken)) {
            \App\Core\Support\Log::error([
                'message' => "Failed createAccessToken for token: {$token}"
            ], 'FirebaseCloudMessaging.sendMessage.$accessToken');

            return null;
        }

        $icon = $icon ?: assets('/assets/icons/icon-192.png');

        $projectId = \readJson('project_id', $this->config);
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
        $payload = [
                        'message' => [
                            'token' => $token,
                            'notification' => [
                                'title' => $title,
                                'body'  => $body,
                            ],
                          // opsi tambahan
                          'webpush' => [
                            'headers' => ['Urgency' => 'high'],
                            'notification' => [
                              'icon' => $icon,
                            ],
                          ],
                        ]
                    ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20
        ]);

        $res = curl_exec($ch);
        if ($res === false) {
            $res = 'cURL error: ' . curl_error($ch);

            \App\Core\Support\Log::error([
                'payload' => $payload,
                'message' => $res
            ], 'FirebaseCloudMessaging.sendMessage.curl($res)');

            return null;
        }

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$code, $res];
    }
}
