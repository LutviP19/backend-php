<?php
declare(strict_types=1);

namespace App\Core\Http;

use App\Core\Security\Middleware\JwtToken;
use App\Core\Support\Config;
use App\Core\Support\Session;
use App\Core\Security\CSRF;
use App\Core\Http\{Request,Response};
use Exception;

/**
 * BaseController class
 * @author Lutvi <lutvip19@gmail.com>
 */
class BaseController
{
    public function __construct()
    {
        
    }

    /**
     * Path to views directory.
     *
     * @var string
     */
    protected $viewPath = __DIR__.'/../../../views/';

    /**
     * Display a view.
     *
     * @param string $view
     * @param array|[] $data
     * @return void
     */
    public function view($view, $data = [])
    {
        if (!$this->exists($view)) {
            throw new Exception("View not Found");
        }

        extract($data);
        require $this->name($view);
        return $this;
    }

    /**
     * Include a view from a view.
     *
     * @param string $view
     * @return void
     */
    public function include($view)
    {
        if (!$this->exists($view)) {
            throw new Exception("Include not found");
        }

        include $this->name($view);
    }

    /**
     * Check if a view exists.
     *
     * @param string $view
     * @return bool
     */
    protected function exists($view)
    {
        return file_exists($this->name($view)) ? true : false;
    }

    /**
     * Get the view name.
     *
     * @param string $view
     * @return string
     */
    protected function name($view)
    {
        $view = str_replace('.', '/', $view);
        return $this->viewPath.$view.'.php';
    }

    /**
     * Set a flash message to session.
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    public function with($key, $value)
    {
        Session::flash($key, $value);
        return $this;
    }

    /**
     * Check for csrf token.
     *
     * @param array $methods
     * @return false
     * @throws \Exception
     */
    protected function csrf($methods = ['POST'])
    {
        $requestMethod = Request::method();

        //uppercase all the methods.
        array_map(function ($method) {
            return strtoupper($method);
        }, $methods);

        if (!in_array($requestMethod, $methods)) {
            return false;
        }

        //check for the csrf token.
        if (!CSRF::match(Request::input('csrf_token'))) {
            $this->response()->statusCode(419);
            throw new Exception("CSRF token not found");
            exit();
        }
    }

    protected function getPass()
    {
        return config('app.token');
    }

    /**
     * initJwtToken function
     *
     * @return void
     */
    public function initJwtToken()
    {
        $secret = Session::get('secret') ?? generateRandomString(32, true);
        $expirationTime = 3600;
        $jwtId = Session::get('jwtId') ?? generateUlid();
        $issuer = clientIP();
        $audience = Config::get('app.url');

        // Init JwtToken
        return (new JwtToken($secret, $expirationTime, $jwtId, $issuer, $audience));
    }

    protected function getBearerToken()
    {
        $headers = $this->request()->headers();

        if (!isset($headers['Authorization'])) {
            return false;
        }

        return str_replace('Bearer ', '', $headers['Authorization']);
    }

    /**
     * getOutput function for formated json response struct
     *
     * @param  boolean $status
     * @param  integer $statusCode
     * @param  array   $data
     * @param  string  $message
     *
     * @return void
     */
    protected function getOutput(bool $status, int $statusCode, array $data, string $message = '')
    {
        if ($status) {
            return [
                'status' => true,
                'statusCode' => $statusCode,
                'message' => $message ?: 'success',
                'data' => $data,
            ];
        } else {
            return [
                'status' => false,
                'statusCode' => $statusCode,
                'message' => $message ?: 'failed',
                'errors' => $data,
            ];
        }
    }

    /**
     * Get the response object.
     *
     * @return \App\Core\Http\Response
     */
    protected function response()
    {
        return new Response();
    }

    /**
     * Get the request object.
     *
     * @return \App\Core\Http\Request
     */
    protected function request()
    {
        return new Request();
    }

}
