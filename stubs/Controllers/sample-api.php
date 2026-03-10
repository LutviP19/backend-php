<?php
/**
 *  @package Backend-PHP
 */

namespace App\Controllers\Api\v1;


use App\Core\Http\{Request, Response};
use App\Controllers\Api\ApiController;
use App\Core\Database\QueryBuilder;
use App\Core\Validation\Validator;


class MyApiController extends ApiController
{
    public function __construct()
    {
        parent::__construct();

        // // State DEV environment
        // $this->isDev = true;

        // $this->useMiddleware();
    }

    public function index(Request $request, Response $response)
    {
        // // Validate token and CSRF
        // $this->validateApiToken(true);


        // $user = Model::table('users')->select(['*'])->get();
        // $roles = Model::table('roles')->select(['id', 'slug', 'name'])->get();
        // $role = Role::getRoleById(3);
        // $userUlid = User::getUlid(3);
        // dd($role, true);
        
        // \App\Core\Support\Log::debug($request->all(), 'MyApiController.index.request');
        return endResponse(
            $this->getOutput(true, 200, [
                'info' => 'This index path',
                'request'=> $request->all(),
            ], 'MyApiController'), 
            200
        );
    }

    public function update()
    {
        // Validate Input
        $validator = new Validator();
        $validator->validate($this->jsonData, [
            'agree_tc' => 'required|string|min:5',
            'regId'  => 'required|string|min:5',
        ]);
        $errors = \App\Core\Support\Session::get('errors');

        if ($errors) {
            $callback = false;

            return endResponse(
                $this->getOutput(false, 422, [
                   $errors
                ]),
                422
            );
        }

        // Filter Input
        $this->jsonData = $this->filter->filter($this->jsonData, [
            'agree_tc' => 'trim|sanitize_string',
            'regId'  => 'trim',
        ]);

        // Sanitize Input
        $payload = $this->filter->sanitize($this->jsonData, ['agree_tc', 'regId']);

        $agree_tc = readJson('agree_tc', $payload);
        $regId = readJson('regId', $payload);

        $regId = \decryptData($regId);
        // Insert / Update into tmp regis
        $query = QueryBuilder::table('tmp_registration')->execQuery('UPDATE tmp_registration SET agree_tc = ?, reg_type = ? WHERE reg_id = ?', [$agree_tc, 'customer', $regId]);

        if (false === $query) {
            $errors = [
                'busy' => ['System busy, please try again in few moments.'],
            ];
            return endResponse(
                $this->getOutput(false, 400, [
                   $errors
                ]),
                400
            );
        }

        return endResponse(
            $this->getOutput(true, 201, [
                'regId' => encryptData($regId),
            ]),
            201
        );
    }
}
