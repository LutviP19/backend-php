<?php

namespace App\Controllers\Api;


use App\Core\Http\{Request, Response};
use App\Core\Message\FirebaseCloudMessaging;
use App\Core\Validation\Validator;
use App\Core\Database\QueryBuilder;

class TestingController extends ApiController
{
    public function index(Request $request, Response $response)    
    {

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

        $regId = \decryptData($regId);
        // Insert / Update into tmp regis
        $query = QueryBuilder::table('tmp_registration')->execQuery('UPDATE tmp_registration SET fcm_token = ?, reg_type = ? WHERE reg_id = ?', [$fcmToken, $regType, $regId]);

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

