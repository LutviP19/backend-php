<?php

declare(strict_types=1);

namespace App\Controllers\ServerApi;

use App\Core\Http\BaseController;
use OpenSwoole\Core\Psr\Response as OpenSwooleResponse;
use App\Core\Security\Encryption;
use App\Core\Security\Middleware\JwtToken;
use App\Core\Support\Config;
use App\Core\Support\Session;
use App\Core\Security\Hash;

class ServerApiController extends BaseController
{
    protected $filter;
    protected $headers;
    protected $jwtToken;

    public function __construct()
    {
        parent::__construct();
        $this->filter = new \App\Core\Validation\Filter();
        $this->headers = getallheaders();

        // \App\Core\Support\Log::debug($_SERVER, 'ServerApiController.__construct.$_SERVER');
        // \App\Core\Support\Log::debug($this->headers, 'ServerApiController.__construct.$this->headers');

        // Clean Errors MessageBag
        Session::unset('errors');

        // Start JWT
        $this->jwtToken = $this->initJwtToken();
    }

    protected function SetOpenSwooleResponse(bool $status, int $statusCode, array $output, string $message = '', array $headers = []): OpenSwooleResponse
    {
        $json = $this->getOutput($status, $statusCode, $output, $message);

        return (new OpenSwooleResponse(\json_encode($json)))
                ->withHeaders(["Content-Type" => "application/json"] + $headers)
                ->withStatus($statusCode);
    }

    /**
     * checkCredentials function
     *
     * @param  [string]  $user
     * @param  [string]  $password
     *
     * @return boolean
     */
    protected function checkCredentials($user, $password): bool
    {
        if ($user) {
            $hash = new Hash();

            if ($hash->matchPassword($password, $user->password)) {
                return true;
            }
        }

        return false;
    }

    public function useMiddleware()
    {
        // Validate header X-Client-Token
        $validate = $this->validateClientToken();
        if($validate) return $validate;

        // Validate Jwt
        $validate = $this->validateJwt();
        if($validate) return $validate;
    }

    /** 
     * validateClientToken function
     *
     * @return OpenSwooleResponseon $status === false, or void
     */
    public function validateClientToken()
    {
        // Set default output
        $status = true;
        $statusCode = 200;
        $output = [];
        $message = '';
        $headers = [];
        
        if (Session::has('uid')) {

            $clientHeaderToken = $this->headers['X-Client-Token'][0] ?? '';

            $clientId = Session::get('uid'); // Get from session
            $validateClient = new \App\Core\Security\Middleware\ValidateClient($clientId);
            $validate = $validateClient->matchToken($clientHeaderToken);

            if (! $validate || empty($validate)) {
                $status = false;
                $statusCode = 401;
                $message = 'Invalid client token!';
                $output = [ 'auth' => 'Invalid token!' ];
            }
        } else {

            $status = false;
            $statusCode = 401;
            $message = 'Please login!';
            $output = [ 'auth' => 'Session expired!' ];
        }

        if (false === $status) {
            return $this->SetOpenSwooleResponse($status, $statusCode, $output, $message, $headers);
        }

        return false;
    }

    public function validateJwt()
    {
        $user = Session::all();
        $tokenJwt = Session::get('tokenJwt');
        $bearerToken = str_replace('Bearer ', '', $this->headers['Authorization'][0] ?? '');

        if (empty($user) ||
            is_null($this->jwtToken) ||
            $bearerToken !== $tokenJwt ||
            false === $this->jwtToken->validateToken($bearerToken)) {

            $statusCode = 401;
            $message = 'Please login!';
            $output = [ 'jwt' => 'Invalid jwt!' ];
            
            return $this->SetOpenSwooleResponse(false, $statusCode, $output, $message);
        }

        return false;
    }

}
