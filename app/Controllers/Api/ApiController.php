<?php

namespace App\Controllers\Api;

use App\Core\Http\BaseController;

class ApiController extends BaseController
{

    /**
     * You can add code that needs to be
     * used in every controller.
     */
  
     protected function getPass() 
     {
        return getenv('HEADER_TOKEN');
     }
}