<?php

namespace App\Controllers\Api;


use App\Core\Http\{Request, Response};
use App\Core\Message\FirebaseCloudMessaging;
use App\Core\Validation\Validator;
use App\Core\Database\QueryBuilder;
use App\Core\Database\Model;
use App\Models\User;
use App\Models\Role;

class TestingController extends ApiController
{
    public function __construct()
    {
        parent::__construct();
        
        // State DEV environment
        $this->isDev = true;

        // dd($this->isDev);
    }

    public function index(Request $request, Response $response)    
    {

        // Validate token and CSRF
        $this->validateApiToken(true);


        $user = Model::table('users')->select(['*'])->get();
        $roles = Model::table('roles')->select(['id', 'slug', 'name'])->get();
        $role = Role::getRoleById(3);
        $userUlid = User::getUlid(3);

        dd($role, true);

        // \App\Core\Support\Log::debug($request->all(), 'TestingController.index.request');
        return endResponse(
            $this->getOutput(true, 200, [
                'info' => 'This index path',
                'request'=> $request->all(),
            ], 'TestingController'), 
            200
        );
    }

    public function saveFcmToken()
    {
        \App\Core\Support\Log::debug($this->jsonData, 'TestingController.saveFcmToken.$this->jsonData');
        // return endResponse(
        //     $this->getOutput(true, 200, [
        //         'info' => 'This saveFcmToken path',
        //         'request'=> $request->all(),
        //     ], 'TestingController'), 
        //     200
        // );

        // Validate Input
        $validator = new Validator();
        $validator->validate($this->jsonData, [
            'fcmToken' => 'required|string|min:5',
            'regId'  => 'required|string|min:5',
            'regType'  => 'required|string|min:5',
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
            'fcmToken' => 'trim',
            'regId'  => 'trim',
            'regType'  => 'trim',
        ]);
        // Sanitize Input
        $payload = $this->filter->sanitize($this->jsonData, ['fcmToken', 'regId', 'regType']);
        // Parse JSON
        $fcmToken = readJson('fcmToken', $payload);
        $regId = readJson('regId', $payload);
        $regType = readJson('regType', $payload);

        // set expiration date
        $dayExpire = 3;
        $expired_seconds = time() + (60 * 60 * 24 * $dayExpire);
        $expiry = date('Y-m-d H:i:s', $expired_seconds);
        // dd($expiry, true);

        $regId = \decryptData($regId);
        // Insert / Update into tmp regis
        $query = QueryBuilder::table('fcm_tokens')->execQuery('REPLACE INTO fcm_tokens (user_id, user_type, token, token_expiry) VALUES (?, ?, ?, ?)', [1, 'userx', $fcmToken, $expiry]);

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
                'fcmToken' => $fcmToken,
            ]),
            201
        );
    }

    public function updateFcmToken()
    {
        // \App\Core\Support\Log::debug($this->jsonData, 'TestingController.saveFcmToken.$this->jsonData');

        // Validate Input
        $validator = new Validator();
        $validator->validate($this->jsonData, [
            'fcmToken' => 'required|string|min:5',
            'userId'  => 'required|string|min:5',
            'userType'  => 'required|string|min:5',
            'forceUpdate'  => 'required',
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
            'fcmToken' => 'trim',
            'userId'  => 'trim',
            'userType'  => 'trim',
            'forceUpdate'  => 'trim',
        ]);
        // Sanitize Input
        $payload = $this->filter->sanitize($this->jsonData, ['fcmToken', 'userId', 'userType', 'forceUpdate']);
        // Parse JSON
        $fcmToken = readJson('fcmToken', $payload);
        $userId = readJson('userId', $payload);
        $userType = readJson('userType', $payload);
        $forceUpdate = readJson('forceUpdate', $payload);
        // dd($forceUpdate, true);

        $userId = \decryptData($userId);
        $table = $userType === 'customer' ? 'customers' : 'drivers';
        $tableId = $userType === 'customer' ? 'customer_id' : 'driver_id';

        // Update
        if($forceUpdate === 'true') {
            // set expiration date
            $dayExpire = 3;
            $expired_seconds = time() + (60 * 60 * 24 * $dayExpire);
            $expiry = date('Y-m-d H:i:s', $expired_seconds);
            // dd($expiry, true);
            
            $query = QueryBuilder::table($table)->execQuery('UPDATE '.$table.' SET fcm_token = ?, fcm_token_expiry = ? WHERE '.$tableId.' = ?', [$fcmToken, $expiry, $userId]);

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

            // Update Session key
            Session::set('fcm_token', $fcmToken);
            Session::set('fcm_token_expiry', $expiry);
        }
        
        
        return endResponse(
            $this->getOutput(true, 201, [
                'fcmToken' => $fcmToken,
            ]),
            201
        );
    }

    public function testFcmToken()
    {
        \App\Core\Support\Log::debug($this->jsonData, 'TestingController.testFcmToken.$this->jsonData');

        $firebaseCloudMessaging = new FirebaseCloudMessaging();

        // Validate Input
        $validator = new Validator();
        $validator->validate($this->jsonData, [
            'fcmToken' => 'required|string|min:5',
            'title'  => 'required|string|min:5',
            'body'  => 'required|string|min:5',
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
            'fcmToken' => 'trim',
            'title'  => 'trim',
            'body'  => 'trim',
        ]);
        // Sanitize Input
        $payload = $this->filter->sanitize($this->jsonData, ['fcmToken', 'title', 'body']);
        // Parse JSON
        $fcmToken = readJson('fcmToken', $payload);
        $title = readJson('title', $payload);
        $body = readJson('body', $payload);

        // Send FCM Notification
        // $accessToken = $firebaseCloudMessaging->createAccessToken();
        $notification = $firebaseCloudMessaging->sendMessage(false, $fcmToken, $title, $body);

        if(is_null($notification)) {
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

        [$code, $res] = $notification;

        $result = \is_string($res) ? json_decode($res, true) : $res;
        \App\Core\Support\Log::debug($result, 'TestingController.testFcmToken.$result');

        return endResponse(
            $this->getOutput(true, $code, [
                'result' => $result,
            ]),
            201
        );
    }

}

