<?php

namespace App\Controllers\Api\v1;

use App\Core\Support\Session;
use App\Core\Support\Config;
use App\Models\User;
use App\Core\Security\Middleware\ValidateClient;
use App\Core\Security\Middleware\JwtToken;
use App\Controllers\Api\ApiController;
use App\Core\Http\{Request,Response};
use App\Core\Security\Hash;
use App\Core\Security\Encryption;
use App\Core\Validation\Validator;
use Exception;
use ReallySimpleJWT\Token;

class WebhookController extends ApiController
{
    public function __construct()
    {
        parent::__construct();

        $this->rateLimit = false;

        // Middlewares
        if ($this->rateLimit) {
            try {
                (new \App\Core\Security\Middleware\RateLimiter('webhook_request'))
                    ->setup(clientIP(), 5, 500, 1200);
            } catch (Exception $exception) {
                die($exception->getMessage());
            }
        }
    }

    // Test Api Server OpenSwoole
    public function bpIndex($request, $data): \Psr\Http\Message\ResponseInterface
    {
        \App\Core\Support\Log::debug($request, 'WebhookController.bpIndex.$request');
        $name = $request->getAttribute('name');
        $name = $data['attributes']['name'] ?: '';

        $output = $this->getOutput(true, 200, [
                        'message' => 'Hello world!, '.$name,
                        'jsonData' => $data['jsonData'],
                        'requestQuery' => $data['requestQuery'],
                    ]);

        return (new \OpenSwoole\Core\Psr\Response(\json_encode($output)))
                ->withHeaders(["Content-Type" => "application/json"])
                ->withStatus(200);
    }

    /**
    * Show the home page.
    *
    * @param App\Core\Http\Request $request
    * @param App\Core\Http\Response $response
    * @return void
    */
    public function index(Request $request, Response $response)
    {
        // global $requestServer;

        // if ( \in_array($_SERVER['SERVER_PORT'], config('app.ignore_port'))) { // on OpenSwoole Server

        //     $headers = $requestServer->header;
        //     $jsonData = \is_string($requestServer->rawContent()) ? \json_decode($requestServer->rawContent(), true) : [];
        // }

        // \App\Core\Support\Log::debug(gettype($request), 'WebhookController.index.gettype($request)');
        // \App\Core\Support\Log::debug($request, 'WebhookController.index.$request');

        // $jsonData = $jsonData ?: $request->all();
        // $filter = new \App\Core\Validation\Filter();

        // Validate Input
        $validator = new Validator();
        $validator->validate($this->jsonData, [
            'email' => 'required|email',
            'password'  => 'required|min:8|max:100',
        ]);
        $errors = \App\Core\Support\Session::get('errors');
        // \App\Core\Support\Log::debug($errors, 'WebhookController.index.errors');


        // $text = 'uid|s:26:"01JP9MA549R9NNVNGHTHJFTNXJ";name|s:5:"Admin";email|s:17:"admin@example.com";password|s:60:"$2y$10$DNGjs3OU3BIvoqCDsxjiCO.VQJe45BO0bUo55LwnMV2ueJ0d6i0WK";client_token|s:88:"MWE0YzYxZjQ0M2NkYTc1NDVlZmY2NmY0ZDQxNDY0MjdlODIzMWZlNGY0NzM0M2U5YzZmOGFlZGY2NTA4MDcyOA==";current_team_id|i:1;profile_photo_path|N;first_name|N;last_name|N;default_url|N;gnr|s:44:"YUBYZFd3Z2hARW5mYjVHS1V1SmdyOEhld3poZUdHNDE=";secret|s:312:"eyJpdiI6IlFUTEkvcDFna2VYTERIT3RoWWR2K1E9PSIsInZhbHVlIjoiTlM0QlRHNGtyOG13WENBcnppbTlZOGQzM0VOVGNsZ09XYS8yb25HeUVNWDJlejdjb1hHNktVNHZXSXAxeDNRR2R6NjkyYnVBWWw0TkdMejBpc21PV3dIemg3WlFaVWttbVZDQnR3OFpDbWM9IiwibWFjIjoiMTc3NzU4ODUzNzRkMzA0MDZiNDNlNDNlZWYxZDIxNzU2YjZiN2EyMmI0YjRjYjcwNDU4OTczZjdkOWQzOGM1MCIsInRhZyI6IiJ9";jwtId|s:26:"01K6K1C3Y3EPZNNDVRHEMZ223C";tokenJwt|s:459:"eyJjdHkiOiJKV1QiLCJpbmZvIjoiQXBpIGp3dC0wMUpQOU1BNTQ5UjlOTlZOR0hUSEpGVE5YSiIsImFsZyI6IkhTMjU2IiwidHlwIjoiSldUIn0.eyJpc3MiOiIxMjcuMC4wLjEiLCJzdWIiOiJBY2Nlc3MgQVBJIGZvciB1c2VyOjAxSlA5TUE1NDlSOU5OVk5HSFRISkZUTlhKIiwiYXVkIjoiaHR0cDpcL1wvbG9jYWxob3N0OjgwMDAiLCJleHAiOjE3NTk0MzE2ODcsIm5iZiI6MTc1OTQyNDQ4NywiaWF0IjoxNzU5NDI4MDg3LCJqdGkiOiIwMUs2SzFDM1kzRVBaTk5EVlJIRU1aMjIzQyIsInVpZCI6IjAxSlA5TUE1NDlSOU5OVk5HSFRISkZUTlhKIn0.uoUW8lfYIMyZeMd3mCPnZTZVoR5LVOlwo3M1oUq3TnM";_previous_uri|s:10:"auth/login";';
        
        // $unz = unserialize($text, ['allowed_classes' => false, 'delemiter' => '|']);
        // \App\Core\Support\Log::debug($unz, 'WebhookController.index.unserialize($text)');


        // // Get cache session data
        // $contentsStr = getRedisContent($_COOKIE[session_name()], 'PHPREDIS_SESSION', '0');
        
        // \App\Core\Support\Log::debug($contentsStr, 'WebhookController.index.cache($contentsStr)');
        // if(! empty($contentsStr)) {
        //     \session_commit();

        //     $contents = unserialize($contentsStr);
        // }
            

        \App\Core\Support\Log::debug($contents, 'WebhookController.index.cache($contents)');


        if ($errors) {
            $callback = false;
            // \App\Core\Support\Log::debug(gettype($callback), 'WebhookController.index.gettype($callback)');

            return endResponse(
                $this->getOutput(false, 203, [
                   $errors
                ]),
                203
            );
        }

        // Filter Input
        $this->jsonData = $this->filter->filter($this->jsonData, [
            'email' => 'trim|sanitize_string',
            'password'  => 'trim|sanitize_string',
        ]);
        // \App\Core\Support\Log::debug($jsonData, 'WebhookController.index.$filtered');

        // Sanitize Input
        $payload = $this->filter->sanitize($this->jsonData, ['email', 'password']);
        // \App\Core\Support\Log::debug($payload, 'WebhookController.index.sanitize.$payload');
        

        // \App\Core\Support\Log::debug($_SERVER, 'WebhookController.index.$_SERVER');
        // \App\Core\Support\Log::debug($_COOKIE, 'WebhookController.index.$_COOKIE');

        // \App\Core\Support\Log::debug($payload, 'WebhookController.index.payload');

        $email = readJson('email', $payload);
        $password = readJson('password', $payload);

        $canRead = readJson('credentials.read', $payload);
        $canWrite = readJson('credentials.write', $payload);
        $canDelete = readJson('credentials.delete', $payload);

        $status = $canRead;
        if ($status) {
            // \App\Core\Support\Log::debug($status, 'WebhookController.index.CREDENTIAL');

            $user = User::getUserByEmail($email);
            // \App\Core\Support\Log::debug($user, 'WebhookController.index.user');
        }


        $hash = new Hash();
        $unik = $hash->unique(32);
        // $unik = $this->getPass();
        // $unik = '01JP9MA549R9NNVNGHTHJFTNXJ';
        $myhash = $hash->create($unik);

        $pass = 'password123';
        $password = $hash->makePassword($pass);

        $clientId =  User::getUlid(1) ?: null;
        // $clientId =  '01JP9MA549R9NNVNGHTHJFTNXJ';

        // Init ValidateClient
        // $validateClient = new ValidateClient(1, 'id');
        $validateClient = new ValidateClient($clientId);

        // Update Token
        // $validateClient->updateToken();
        // (new User())->updateClientToken('ulid', $clientId);

        // Get Token
        $clientToken = $validateClient->generateToken();
        $clientStrToken = $validateClient->getToken();

        // generate ULID
        $ulid = generateUlid();

        // JWT
        $userId = $clientId;
        $secret = $clientStrToken;
        $expirationTime = 3600;
        $jwtId = generateUlid();
        $issuer = clientIP();
        $audience = Config::get('app.url');
        // Init JwtToken
        $jwtToken = new JwtToken($secret, $expirationTime, $jwtId, $issuer, $audience);
        // create specific Token
        $info = 'Webhook jwt';
        $subject = 'Access Webhook API';
        $tokenJwt = $jwtToken->createToken($userId, $info, $subject);

        $token_api = encryptData($this->getPass());
        $output = $this->getOutput(true, 200, [
                'message' => 'Hello world!',
                'client_ip' => clientIP(),
                'session_id' => session_id(),
                'ulid' => $ulid,
                'token_jwt' => $tokenJwt,
                'parse_jwt' => $jwtToken->parseJwt($tokenJwt),
                'match_jwt' => $jwtToken->validateToken($tokenJwt),
                'token' => $clientToken,
                'str_token' =>  $clientStrToken,
                'match_token' => $validateClient->matchToken($clientToken),
                'strlen' => strlen('5gbSVtgMFs96tGNGyBKVyjwREtj6uzPHmVnauvyhFpkLuZXEW4GIh8HGM2lW'),
                'genkey' => Encryption::generateKey(),
                'pass' => $token_api,
                'decrypt_pass' => \decryptData($token_api),
                'match_pass' => matchEncryptedData($this->getPass(), $token_api),
                'myhash' => $myhash,
                'check_hash' => $hash->matchHash($unik, $myhash),
                'password' => $password,
                'check_pass' => $hash->matchPassword($pass, $password),
                'rand_str' => generateRandomString(16),
                'unique' => $hash->unique(32),
                'user' => Session::all()
            ], 'WEBHOOK EXAMPLE');
        // \App\Core\Support\Log::debug($output, 'WebhookController');

        return endResponse($output, 200);
    }
}
