<?php

namespace App\Controllers\Api\v1\Auth;

use App\Core\Support\Session;
use App\Core\Security\Middleware\ValidateClient;
use App\Core\Security\Middleware\JwtToken;
use App\Core\Support\Config;
use App\Models\User;
use App\Core\Security\Hash;
use App\Core\Validation\Validator;
use App\Controllers\Api\ApiController;
use App\Core\Http\{Request,Response};

class AuthController extends ApiController
{

    protected $id;
    protected $ulid;

    public function __construct()
    {
        parent::__construct();
    }

    public function login(Request $request,Response $response)
    {
        // $payload = $request::getPayload();
        // \App\Core\Support\Log::debug($payload, 'AuthController.login');

        try {
            $validator = new Validator;
            $validator->validate($request, [
                'email' => 'required|email',
                'password'  => 'required|min:8|max:100',
            ]);
            $errors = errors()->all();

            
            if($errors) {

                $status = 203;
                $callback = false;
            } else {

                $status = 429;
                $errors = ['auth' => 'Invalid credentials,',];
                
                $payload = $request->all();
                $email = readJson('email', $payload);
                $password = readJson('password', $payload);

                $user = User::getUserByEmail($email);
                $callback = $this->checkCredentials($user, $password);

                // if($callback)
                // \App\Core\Support\Log::debug($user, 'AuthController.login.$callback');
            }

            // Middleware
            (new \App\Core\Security\Middleware\RateLimiter('login_request'))
                ->setupForm(clientIP(), $callback, 5, 10, 1200);

            if(false == $callback || empty($user)) {
                die(
                    $response->json(
                       $this->getOutput(false, $status, [
                            $errors
                       ])
                    , $status)
                );
            }
        }
        catch(Exception $exception) {
            die(
                $response->json(
                   $this->getOutput(false, 429, [
                      $exception->getMessage(),
                   ])
                , 429)
             );
        }

        // Generate credentials token
        // JWT
        $userId = $user->ulid;
        $secret = $user->client_token;
        $expirationTime = 3600;
        $jwtId = generateUlid();
        $issuer = clientIP();
        $audience = Config::get('app.url');
        // Init JwtToken
        $jwtToken = new JwtToken($secret, $expirationTime, $jwtId, $issuer, $audience);
        // create specific Token
        $info = 'Api jwt';
        $subject = 'Access API';
        $tokenJwt = $jwtToken->createToken($userId, $info, $subject);

        // Set login session
        $validateClient = new ValidateClient($userId);
        $clientToken = $validateClient->generateToken();

        if(false === $validateClient->matchToken($clientToken)) {
            die(
                $response->json(
                   $this->getOutput(false, 429, [
                      'message' => 'Invalid client Id,',
                   ])
                , 429)
             );
        }

        // Session::destroy();
        foreach($user as $key => $value) {
            if($key === 'ulid')
                $key = 'uid';

            if($key === 'client_token')
                $value = $clientToken;

            Session::set($key, $value);
        }

        return $response->json($this->getOutput(true, 201, [
                    'token' => $tokenJwt,
                    'account' => Session::all()
                ]), 201);
    }

    private function checkCredentials($user, $password): bool
    {
        if($user) {
            $hash = new Hash();

            if($hash->matchPassword($password, $user->password))
                return true;
        }

        return false;
    }
}