<?php

namespace App\Core\Http;

use App\Core\Support\Session;
use App\Core\Http\Request;

/**
 * Response class
 * @author Lutvi <lutvip19@gmail.com>
 */
class Response
{
    /**
     * Current request object.
     * @var Request
     */
    protected $request;

    /**
     * Base Controller object.
     * @var BaseController
     */
    protected $controller;

    /**
     * Penampung header yang di-set di controller
     */
    protected array $headers = [];

    /**
     * HTTP Status Code default
     */
    protected int $statusCode = 200;

    // Property to store the response body
    protected mixed $content = '';

    /**
     * Magic method called when the instance
     * is created.
     *
     * @return void
     */
    public function __construct()
    {
        $this->setRequest(new Request());
        $this->setController(new BaseController());
    }

    /**
     * Set Header
     */
    public function header($key, $value, $statusCode = null, $replace = true)
    {
        // ONLY updates the status code if $statusCode is filled in and is a valid integer
        if (is_numeric($statusCode) && (int)$statusCode >= 100) {
            $this->statusCode = (int)$statusCode;
        }

        // Save the header to the container array
        $this->headers[$key] = $value;

        // Note: PHP's native header() is called when running in a non-Swoole/FPM environment
        if (!headers_sent() && php_sapi_name() !== 'cli') {
            header("{$key}: {$value}", $replace, $this->statusCode);
        }

        return $this;
    }

    /**
     * Take all the headers that have been saved
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Set status code HTTP
     */
    public function status(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * set a response code.
     *
     * @param int $code
     * @return Response
     */
    public function statusCode($code)
    {
        http_response_code($code);
        return $this;
    }

    /**
     * set a response code.
     *
     * @param int $code
     * @return Response
     */
    public function getStatusCode()
    {
        // Provide a fallback of 200 if the value is somehow cast to false/empty
        return (is_int($this->statusCode) && $this->statusCode > 0) ? $this->statusCode : 200;
    }

    /**
     * Redirect to a specific URL or pass a status code to trigger an error status.
     *
     * @param string|int $url URL destination or HTTP status code error
     * @param int $statusCode
     * @return self
     */
    public function redirect(string|int $url, int $statusCode = 302): self
    {
        // Handle if the first parameter is passed an integer status code (eg: redirect(404))
        if (is_int($url)) {
            $this->httpError($url);
            return $this;
        }

        // Set HTTP Status Code untuk Redirect (default 302)
        $this->status($statusCode);

        // Set standard HTTP Location header
        $this->header('Location', $url);

        // If the request comes from HTMX, add HX-Redirect
        // HTMX will perform a seamless client-side redirect without full page reload if desired
        if (\isHtmx()) {
            $this->header('HX-Redirect', $url);
        }

        return $this;
    }

    /**
     * Send Response to Client (FPM & OpenSwoole Compatible)
     *
     * @param mixed $swooleResponse Objek OpenSwoole\Http\Response (Optional if in Swoole environment)
     * @return void
     */
    public function send($swooleResponse = null): void
    {
        if ($swooleResponse && is_object($swooleResponse) && method_exists($swooleResponse, 'end')) {
            $swooleResponse->status($this->statusCode);

            foreach ($this->headers as $name => $value) {
                $swooleResponse->header($name, $value);
            }

            $swooleResponse->end($this->content);
            return;
        }

        // --- PHP-FPM PATH / STANDARD HTTP ---
        if (!headers_sent()) {
            http_response_code($this->statusCode);

            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }

        echo $this->content;
    }

    /**
     * Redirect to the previous url.
     *
     * @return void
     */
    public function redirectBack()
    {
        $this->redirect($this->getRequest()->previousUrl());
    }

    /**
     * return json response.
     *
     * @param mixed|[] $data
     * @param int $code
     * @return mixed
     */
    public function json($data = [], $code = 200)
    {
        if (! isSwoole()) { // Ignore OpenSwoole Server

            $this->header("Content-Type", "application/json; charset=utf-8", $code);

            print json_encode($data, JSON_UNESCAPED_SLASHES);
            // exit;
            customExit();
        } else {

            // print json_encode($data, JSON_UNESCAPED_SLASHES);
            return [
                'code' => $code,
                'data' => $data
            ];
        }

    }

    /**
     * Store a session value for the next
     * request (flash message).
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function with($key, $value)
    {
        Session::flash($key, $value);
    }

    /**
     * Force an http error and display an error
     * view.
     *
     * @param int $code
     * @return void
     */
    protected function httpError($code)
    {
        // switch ($code) {
        //     case 403:
        //         //403 forbidden!
        //         $this->sendErrorResponse($code, 'errors.403');
        //         break;
        //     case 404:
        //         //404 not found!
        //         $this->sendErrorResponse($code, 'errors.404');
        //         break;
        //     case 503:
        //         //503 service unavailable!
        //         $this->sendErrorResponse($code, 'errors.503');
        //         break;
        //     case 500:
        //     default:
        //         //500 internal server error!
        //         $this->sendErrorResponse($code, 'errors.500');
        //         break;
        // }

        match ($code) {
            //403 forbidden!
            403 => $this->sendErrorResponse($code, 'errors.403'),
            //404 not found!
            404 => $this->sendErrorResponse($code, 'errors.404'),
            //503 service unavailable!
            503 => $this->sendErrorResponse($code, 'errors.503'),
            //500 internal server error!
            default => $this->sendErrorResponse($code, 'errors.500'),
        };
    }

    /**
     * Send the error response code if requests json or
     * else just return the appropriate view.
     *
     * @param int $code
     * @param string $view
     * @return void
     */
    protected function sendErrorResponse($code, $view)
    {
        if ($this->getRequest()->isJsonRequest()) {
            $this->statusCode($code);
        } else {
            $this->getController()->view($view);
        }
    }

    /**
     * Set the body contents of the response
     */
    public function setContent(mixed $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Take the body of the response
     */
    public function getContent(): mixed
    {
        return $this->content;
    }

    /**
     * Set current request object.
     *
     * @param Request $request
     * @return void
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Get current request object.
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Set the controller object.
     *
     * @param BaseController $controller
     * @return void
     */
    public function setController(BaseController $controller)
    {
        $this->controller = $controller;
    }

    /**
     * Get controller object.
     *
     * @return BaseController
     */
    public function getController()
    {
        return $this->controller;
    }

}
