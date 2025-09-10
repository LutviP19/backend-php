<?php

namespace App\Controllers\Api;

use App\Core\Http\{Request,Response};
use App\Core\Security\Encryption;
use App\Core\Http\BaseController;

class ApiController extends BaseController
{
   public function __construct() {
      parent::__construct();

      // Accepted type is JSON
      if(false === $this->request()->isJsonRequest())
         die('Only accepted JSON.');

      // Middlewares
      (new \App\Core\Security\Middleware\EnsureIpIsValid())
         ->handle($this->request(), $this->response());
      (new \App\Core\Security\Middleware\EnsureHeaderIsValid())
         ->handle($this->request(), $this->response());

      // Validate token
      $this->validateToken($this->request(), $this->response());
   }

   /**
   * You can add code that needs to be
   * used in every controller.
   */
   protected function getPass() 
   {
      return config('app.token');
   }

   protected function getOutput(bool $status, int $statusCode, array $data)
   {
      if($status) {
         return [
            'status' => true,
            'statusCode' => $statusCode,
            'message' => 'success',
            'data' => $data
         ];
      } else {
         return [
            'status' => false,
            'statusCode' => $statusCode,
            'message' => 'failed',
            'errors' => $data
         ];
      }
   }

   public function validateToken(Request $request, Response $response) 
   {
      $header = $request->headers();

      if(isset($header['Api-Token']) === false || 
      matchEncryptedData($this->getPass(), $header['Api-Token']) === false) {
         die(
            $response->json(
               $this->getOutput(false, 403, [
                  // 'token' => $this->getPass(),
                  'message' => 'token missmatch!',
               ])
            , 403)
         );
      }
   }
}