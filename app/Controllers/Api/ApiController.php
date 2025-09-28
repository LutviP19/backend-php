<?php

namespace App\Controllers\Api;

use App\Core\Http\BaseController;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Security\Encryption;
use App\Core\Security\Middleware\JwtToken;
use App\Core\Support\Config;
use App\Core\Support\Session;

/**
 * ApiController class
 * @author Lutvi <lutvip19@gmail.com>
 */
class ApiController extends BaseController
{
    protected $jwtToken;

    public function __construct()
    {
        parent::__construct();

        // \App\Core\Support\Log::debug($_SERVER['SERVER_PORT'], 'ApiController.SERVER_PORT');
        if (! \in_array($_SERVER['SERVER_PORT'], config('app.ignore_port'))) { // OpenSwoole Server
            // Accepted type is JSON
            if (false === $this->request()->isJsonRequest()) {
                die(
                    $this->response()->json(
                        $this->getOutput(false, 403, [
                            'Invalid format!',
                        ], 'Only accepted JSON.'),
                        406
                    )
                );
            }

            // Middlewares
            (new \App\Core\Security\Middleware\EnsureIpIsValid())
                ->handle($this->request(), $this->response());
            (new \App\Core\Security\Middleware\EnsureHeaderIsValid())
                ->handle($this->request(), $this->response());

            // Validate token
            $this->validateToken($this->request(), $this->response());

            // Validate with session data
            if (Session::has('uid') && Session::has('secret') && Session::has('jwtId')) {
                // JWT
                $this->jwtToken = $this->initJwtToken();
            }
        }
    }

    /**
     * You can add code that needs to be
     * used in every controller.
     */

    /**
     * validateToken function
     *
     * @param  Request  $request
     * @param  Response $response
     *
     * @return void
     */
    public function validateToken(Request $request, Response $response)
    {
        $header = $request->headers();

        if (isset($header['X-Api-Token']) === false ||
            matchEncryptedData($this->getPass(), $header['X-Api-Token']) === false) {

            die(
                $response->json(
                    $this->getOutput(false, 403, [
                        'token' => 'Invalid api token!',
                    ], 'Invalid api token!'),
                    403
                )
            );
        }
    }

    /**
     * validateClientToken function
     *
     * @param  Request  $request
     * @param  Response $response
     *
     * @return void
     */
    public function validateClientToken(Request $request, Response $response)
    {
        $header = $request->headers();

        $clientId = Session::get('uid'); // Get from session
        $validateClient = new \App\Core\Security\Middleware\ValidateClient($clientId);
        // $clientToken = $validateClient->getToken();

        if (empty($clientId)) {

            die(
                $response->json(
                    $this->getOutput(false, 401, [
                        'auth' => 'Session expired!',
                    ], 'Please login!'),
                    401
                )
            );
        }

        if (isset($header['X-Client-Token']) === false ||
            $validateClient->matchToken($header['X-Client-Token']) === false) {

            die(
                $response->json(
                    $this->getOutput(false, 403, [
                        'client_token' => 'Invalid client token!',
                    ], 'Invalid client token!'),
                    403
                )
            );
        }
    }

    public function validateJwt(Request $request, Response $response)
    {
        $user = Session::all();
        $tokenJwt = Session::get('tokenJwt');
        $bearerToken = $this->getBearerToken();

        if (empty($user) ||
            is_null($this->jwtToken) ||
            $bearerToken !== $tokenJwt ||
            false === $this->jwtToken->validateToken($bearerToken)) {

            die(
                $response->json(
                    $this->getOutput(false, 401, [
                        'jwt' => 'Invalid jwt!',
                    ], 'Please login!'),
                    401
                )
            );
        }
    }

    /**
     * initJwtToken function
     *
     * @return void
     */
    public function initJwtToken()
    {
        $secret = Session::get('secret');
        $expirationTime = 3600;
        $jwtId = Session::get('jwtId');
        $issuer = clientIP();
        $audience = Config::get('app.url');

        // Init JwtToken
        return (new JwtToken($secret, $expirationTime, $jwtId, $issuer, $audience));
    }

    protected function getPass()
    {
        return config('app.token');
    }

    protected function getBearerToken()
    {
        $headers = $this->request()->headers();

        if (!isset($headers['Authorization'])) {
            return false;
        }

        return str_replace('Bearer ', '', $headers['Authorization']);
    }
    
}
