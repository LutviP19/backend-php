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

        // Session::unset('errors');
        // Validate with session data
        if (Session::has('uid') && Session::has('secret') && Session::has('jwtId')) {
            // JWT
            $this->jwtToken = $this->initJwtToken();
        }
    }

    protected function SetOpenSwooleResponse(bool $status, int $statusCode, array $output, string $message = '', array $headers = []) : OpenSwooleResponse
    {
        $json = $this->getOutput($status, $statusCode, $output, $message);

        return (new OpenSwooleResponse(\json_encode($json)))
                ->withHeaders(["Content-Type" => "application/json"] + $headers)
                ->withStatus($statusCode);
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

}