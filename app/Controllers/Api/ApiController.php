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
      if(! $this->request()->isJsonRequest()) {
         die(
            $response->json(
               $this->getOutput(false, 403, [
                  'message' => 'Only accepted JSON.',
               ])
            , 403)
         );
      }

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

      if(isset($header['X-Api-Token']) === false || 
      matchEncryptedData($this->getPass(), $header['X-Api-Token']) === false) {
         die(
            $response->json(
               $this->getOutput(false, 403, [
                  'message' => 'Invalid api token!',
               ])
            , 403)
         );
      }
   }

   public function validateClientToken(Request $request, Response $response) 
   {
      $header = $request->headers();

      $clientId = '01JP9MA549R9NNVNGHTHJFTNXJ';  // Get from session
      $validateClient = new \App\Core\Security\Middleware\ValidateClient($clientId);
      // $clientToken = $validateClient->getToken();

      if(isset($header['X-Client-Token']) === false || 
         $validateClient->matchToken($header['X-Client-Token']) === false) {
            die(
               $response->json(
                  $this->getOutput(false, 403, [
                     'message' => 'Invalid client PIN!',
                  ])
               , 403)
            );
      }
   }
}