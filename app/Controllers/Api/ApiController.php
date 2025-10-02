<?php 
declare(strict_types=1);

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
    protected $filter;
    protected $requestServer;
    protected $headers;
    protected $jsonData;
    protected bool $rateLimit;

    public function __construct()
    {
        global $requestServer;

        parent::__construct();

        $this->rateLimit = false;
        $this->filter = new \App\Core\Validation\Filter();
        $this->requestServer = $requestServer;

        
        if ( \in_array($_SERVER['SERVER_PORT'], config('app.ignore_port'))) { // on OpenSwoole Server

            // Format Headers
            foreach($this->requestServer->header as $key => $value) {
                $this->headers[ucwords($key, "-")] = $value;
            }

            $rawBody = $this->requestServer->rawContent();
            if (! empty($rawBody) && checkValidJSON($rawBody)) {
                $this->jsonData = \is_string($rawBody) ? \json_decode($rawBody, true, 512, \JSON_BIGINT_AS_STRING | \JSON_THROW_ON_ERROR) : [];
            } else {
                $this->jsonData = [];
            }
        } else {

            $this->headers = getallheaders();

            $rawBody =  \request()->getBody();
            $this->jsonData = \request()->all();
        }

        // Accepted type is JSON
        $validate = $this->onlyAcceptedJSON();
        if ($validate) return $validate;
        
        // Validate JSON
        $validate = $this->checkValidJSON($rawBody);
        if ($validate) return $validate;

        // Clean Errors MessageBag
        Session::unset('errors');

        // \App\Core\Support\Log::debug($_SERVER, 'ApiController._SERVER');
        // \App\Core\Support\Log::debug($this->headers, 'ApiController.$this->headers');
        // \App\Core\Support\Log::debug($_SERVER['HTTP_ACCEPT'], 'ApiController.HTTP_ACCEPT');
        // \App\Core\Support\Log::debug($this->request()->isJsonRequest(), 'ApiController.$request');
        
        // if (! \in_array($_SERVER['SERVER_PORT'], config('app.ignore_port'))) { // ignore OpenSwoole Server
            

            // Middlewares
            (new \App\Core\Security\Middleware\EnsureIpIsValid())->handle();
            (new \App\Core\Security\Middleware\EnsureHeaderIsValid())->handle($this->headers);

            // Validate token
            $this->validateApiToken();

            // Validate with session data
            if (Session::has('uid') && Session::has('secret') && Session::has('jwtId')) {
                // JWT
                $this->jwtToken = $this->initJwtToken();
            }
        // }
    }

    public function useMiddleware($guest = false)
    {
        if($guest) {
            // Validate Api token
            $this->validateApiToken();
        } else {
            // Validate header X-Client-Token
            // \App\Core\Support\Log::debug($this->headers, 'WebAuth.updateToken.$headers');
            $validate = $this->validateClientToken();
            if($validate) return $validate;

            // Validate Jwt
            $validate = $this->validateJwt();
            if($validate) return $validate;

            // Validate using session data
            if (Session::has('uid') && Session::has('secret') && Session::has('jwtId')) {
                // ValidateSession
                $validate = (new \App\Core\Security\Middleware\ValidateSession())->handle();
                if($validate) return $validate;
            }
        }
    }

    public function onlyAcceptedJSON()
    {
        if ($this->headers['Accept'] !== 'application/json' || 
        $this->headers['Content-Type'] !== 'application/json' ||
        $_SERVER['HTTP_ACCEPT'] !== 'application/json') {

            return stopHere(
                $this->getOutput(false, 406, [
                    'Invalid Content Type!',
                ], 'Only accepted JSON.'),
                406
            );
        }

        return;
    }

    public function checkValidJSON($rawBody) 
    {
        
        if ($rawBody === '') {

            return stopHere(
                $this->getOutput(false, 406, [
                    'Invalid data!'
                ], 'Invalid Data.'),
                406
            );
        }
        $validBody = json_decode(trim($rawBody), true);
    
        if (json_last_error() !== JSON_ERROR_NONE) {
        
            return stopHere(
                $this->getOutput(false, 406, [
                    'Invalid Json format!'
                ], 'Invalid JSON.'),
                406
            );
        }

        return '';
    }

    /**
     * validateApiToken function
     *
     * @param  Request  $request
     * @param  Response $response
     *
     * @return void
     */
    public function validateApiToken()
    {
        // $header = $this->headers;

        if (isset($this->headers['X-Api-Token']) === false ||
            matchEncryptedData($this->getPass(), $this->headers['X-Api-Token']) === false) {

            return stopHere(
                $this->getOutput(false, 403, [
                    'token' => 'Invalid api token!',
                ], 'Invalid api token!'),
                403
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
    public function validateClientToken()
    {
        // $header = $request->headers();

        $clientId = Session::get('uid'); // Get from session
        $validateClient = new \App\Core\Security\Middleware\ValidateClient($clientId);
        // $clientToken = $validateClient->getToken();

        \App\Core\Support\Log::debug($clientId, 'ApiController.validateClientToken.clientId');
        if (empty($clientId)) {
            return stopHere(
                $this->getOutput(false, 401, [
                    'auth' => 'Session expired!',
                ], 'Please login!'), 
                401
            );
        }

        // \App\Core\Support\Log::debug(isset($header['X-Client-Token']), 'ApiController.validateClientToken.X-Client-Token');        
        if(! isset($this->headers['X-Client-Token'])) {
            return stopHere(
                $this->getOutput(false, 403, [
                        'client_token' => 'Missing client token!',
                    ], 'Missing client token header!'),
                403
            );
        }
        
        if(! $validateClient->matchToken($this->headers['X-Client-Token'])) {

            return stopHere(
                $this->getOutput(false, 403, [
                        'client_token' => 'Invalid client token!',
                    ], 'Invalid client token!'),
                403
            );
        }
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

            return stopHere(
                $this->getOutput(false, 401, [
                    'jwt' => 'Invalid jwt!',
                ], 'Please login!'), 
                401
            );
        }
    }

}
