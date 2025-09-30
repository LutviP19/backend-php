<?php
declare(strict_types=1);

namespace App\Controllers\ServerApi;

use App\Core\Http\BaseController;
use OpenSwoole\Core\Psr\Response as OpenSwooleResponse;
use App\Core\Security\Encryption;
use App\Core\Security\Middleware\JwtToken;
use App\Core\Support\Config;
use App\Core\Support\Session;

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

        if (session_status() != PHP_SESSION_ACTIVE) {
            session_start();
        }
        Session::unset('errors');
    }

    protected function SetOpenSwooleResponse(bool $status, int $statusCode, array $output, string $message = '', array $headers = []) : OpenSwooleResponse
    {
        $json = $this->getOutput($status, $statusCode, $output, $message);

        return (new OpenSwooleResponse(\json_encode($json)))
                ->withHeaders(["Content-Type" => "application/json"] + $headers)
                ->withStatus($statusCode);
    }

    public function useMiddleware() {
        // if (stripos($this->headers['Request-Uri'], '/api') === 0) {
            // \App\Core\Support\Log::debug($this->headers['Request-Uri'], 'ServerApiController.__construct.Request-Uri');

            // Validate credentials
            $this->validateClientToken();

            // Validate with session data
            if (Session::has('uid') && Session::has('secret') && Session::has('jwtId')) {
                // JWT
                $this->jwtToken = $this->initJwtToken();
            }
        // }
    }

    public function validateJwt()
    {
        $user = Session::all();
        $tokenJwt = Session::get('tokenJwt');
        $bearerToken = $this->getBearerToken();

        if (empty($user) ||
            is_null($this->jwtToken) ||
            $bearerToken !== $tokenJwt ||
            false === $this->jwtToken->validateToken($bearerToken)) {

            $statusCode = 401;
            $output = [
                        'jwt' => 'Invalid jwt!',
                    ];
            return $this->SetOpenSwooleResponse(false, $statusCode, $exception->getMessage(), 'Please login!');
        }
    }

    /**
     * validateClientToken function
     *
     * @return OpenSwooleResponseon $status === false, or void
     */
    public function validateClientToken()
    {
        // Set default output
        $status = true; $statusCode = 200; $output = []; $message = ''; $headers = [];

        if(Session::has('uid')) {
            
            $clientId = Session::get('uid'); // Get from session
            $validateClient = new \App\Core\Security\Middleware\ValidateClient($clientId);
            $validate = $validateClient->matchToken($clientHeaderToken);

            if (! $validate || empty($validate)) {
                $status = false; $statusCode = 401; $message = 'Invalid client token!';
                $output = [ 'auth' => 'Invalid token!' ];
            }
    
        } else {
            $status = false; $statusCode = 401; $message = 'Please login!';
            $output = [ 'auth' => 'Session expired!' ];
        }
        
        
        if(false === $status) {
            \App\Core\Support\Log::debug($status, 'ServerApiController.validateClientToken.status');
            return $this->SetOpenSwooleResponse($status, $statusCode, $output, $message, $headers);
        }
    }

}