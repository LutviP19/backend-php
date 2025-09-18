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
use Exception;

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

                $status = 401;
                $errors = ['auth' => 'Invalid credentials,',];
                
                $payload = $request->all();
                $email = readJson('email', $payload);
                $password = readJson('password', $payload);

                $user = User::getUserByEmail($email);
                $callback = $this->checkCredentials($user, $password);
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
        
        // Session::destroy();
        foreach($user as $key => $value) {
            if($key === 'ulid')
                $key = 'uid';

            Session::set($key, $value);
        }

        $userId = $user->ulid;

        // Set login session
        $validateClient = new ValidateClient($userId);
        $clientToken = $validateClient->generateToken();
        Session::set('client_token', $clientToken);

        if(false === $validateClient->matchToken($clientToken)) {
            die(
                $response->json(
                   $this->getOutput(false, 401, [
                      'message' => 'Invalid client Id!',
                   ])
                , 401)
             );
        }

        // initJwtToken
        Session::set('secret', $user->client_token);
        Session::set('jwtId', generateUlid());
        $this->jwtToken = $this->initJwtToken();        

        // Create specific data for jwt
        $info = 'Api jwt-'.$userId;
        $subject = 'Access API for user:'.$userId;
        $tokenJwt =  $this->jwtToken->createToken($userId, $info, $subject);
        Session::set('tokenJwt', $tokenJwt);

        return $response->json($this->getOutput(true, 201, [
                    'token' => $tokenJwt,
                    'sessid' => session_id(),
                    'account' => Session::all()
                ]), 201);
    }

    public function updateToken(Request $request,Response $response)
    {
        // Validate header X-Client-Token
        $this->validateClientToken($request, $response);

        // Validate JWT
        $this->validateJwt($request, $response);

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

                $status = 401;
                $errors = ['auth' => 'Invalid credentials,',];
                
                $payload = $request->all();
                $email = readJson('email', $payload);
                $password = readJson('password', $payload);

                $user = User::getUserByEmail($email);
                $callback = $this->checkCredentials($user, $password);
            }

            // Middleware
            (new \App\Core\Security\Middleware\RateLimiter('uptoken_request'))
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
        
        // Update Client Token
        $userId = Session::get('uid');
        $validateClient = new ValidateClient($userId);
        $validateClient->updateToken();

        Session::destroy();

        return $response->json($this->getOutput(true, 201, [
            'auth' => 'Token successfully updated, please re-login to use new token!',
        ]), 201);
    }

    public function logout(Request $request,Response $response)
    {
        // Validate header X-Client-Token
        $this->validateClientToken($request, $response);

        // Validate JWT
        $this->validateJwt($request, $response);

        // clear cache token
        $userId = Session::get('uid');
        $validateClient = new ValidateClient($userId);
        $validateClient->delToken();

        Session::destroy();

        return $response->json($this->getOutput(true, 200, [
            'auth' => 'You are logged out!',
        ]), 200);
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