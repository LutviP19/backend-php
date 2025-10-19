<?php

namespace App\Controllers\Api\v1\Registration;

use App\Core\Database\QueryBuilder;
use App\Models\Customer;
use App\Models\TmpRegistration;
use App\Controllers\Api\ApiController;
use App\Core\Http\{Request, Response};
use App\Core\Validation\Validator;

/*
*   Registration Customer
* 
*/
class RegCustomerController extends ApiController
{
    public function __construct()
    {
        parent::__construct();

        // $this->useMiddleware();
    }

    public function step1()
    {
        // Validate Input
        $validator = new Validator();
        $validator->validate($this->jsonData, [
            'name' => 'required|string|min:3|max:100',
            'phone'  => 'required|numeric|min:10|max:20',
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
            'name' => 'trim|sanitize_string',
            'phone'  => 'trim|sanitize_numbers',
        ]);
        // Sanitize Input
        $payload = $this->filter->sanitize($this->jsonData, ['name', 'phone']);
        // Parse JSON
        $name = readJson('name', $payload);
        $phone = readJson('phone', $payload);
        // Check phone number already used
        $phoneExists = Customer::select()->where('phone_number', '=', $phone)->first();
        if ($phoneExists) {

            $errors = [
                'phone' => ['Phone number already registred.']
            ];
            return endResponse(
                $this->getOutput(false, 422, [
                   $errors
                ]),
                422
            );
        }

        // Insert / Update into tmp regis
        $regId = QueryBuilder::table('tmp_registration')->execQuery('REPLACE INTO tmp_registration (full_name, phone_number, reg_type) VALUES (?, ?, ?)', [$name, $phone, 'customer'], true);

        if (false === $regId || ! \is_numeric($regId)) {
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

    public function step2()
    {
        // Validate Input
        $validator = new Validator();
        $validator->validate($this->jsonData, [
            'allow_notification' => 'required|string|min:5',
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
            'allow_notification' => 'trim|sanitize_string',
            'regId'  => 'trim',
        ]);
        // Sanitize Input
        $payload = $this->filter->sanitize($this->jsonData, ['allow_notification', 'regId']);
        // Parse JSON
        $allow_notification = readJson('allow_notification', $payload);
        $regId = readJson('regId', $payload);

        $regId = \decryptData($regId);
        // Insert / Update into tmp regis
        $query = QueryBuilder::table('tmp_registration')->execQuery('UPDATE tmp_registration SET allow_notification = ?, reg_type = ? WHERE reg_id = ?', [$allow_notification, 'customer', $regId]);

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

    public function step3()
    {
        // Validate Input
        $validator = new Validator();
        $validator->validate($this->jsonData, [
            'pin' => 'required|numeric|min:4|max:4',
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
            'pin' => 'trim|sanitize_numbers',
            'regId'  => 'trim',
        ]);

        // Sanitize Input
        $payload = $this->filter->sanitize($this->jsonData, ['pin', 'regId']);
        // parse JSON
        $pin = readJson('pin', $payload);
        $regId = readJson('regId', $payload);

        $regId = \decryptData($regId);
        // Insert / Update into tmp regis
        $query = QueryBuilder::table('tmp_registration')->execQuery('UPDATE tmp_registration SET pin = ?, reg_type = ? WHERE reg_id = ?', [$pin, 'customer', $regId]);

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

    public function step4()
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

    public function step5()
    {
        // Validate Input
        $validator = new Validator();
        $validator->validate($this->jsonData, [
            'allow_enabled_location' => 'required|string|min:5',
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
            'allow_enabled_location' => 'trim|sanitize_string',
            'regId'  => 'trim',
        ]);

        // Sanitize Input
        $payload = $this->filter->sanitize($this->jsonData, ['allow_enabled_location', 'regId']);

        $allow_enabled_location = readJson('allow_enabled_location', $payload);
        $regId = readJson('regId', $payload);

        $regId = \decryptData($regId);
        // Insert / Update into tmp regis
        $query = QueryBuilder::table('tmp_registration')->execQuery('UPDATE tmp_registration SET allow_enabled_location = ?, reg_type = ? WHERE reg_id = ?', [$allow_enabled_location, 'customer', $regId]);

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

    public function regFinish()
    {
        // Validate Input
        $validator = new Validator();
        $validator->validate($this->jsonData, [
            'address' => 'required|string|min:5',
            'regType' => 'required|string|min:5',
            'regId'  => 'required|string|min:5',
        ]);
        $errors = \App\Core\Support\Session::get('errors');
        // \App\Core\Support\Log::debug($errors, 'RegCustomerController.index.errors');

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
            'address' => 'trim|sanitize_string',
            'regType' => 'trim|sanitize_string',
            'regId'  => 'trim',
        ]);

        // Sanitize Input
        $payload = $this->filter->sanitize($this->jsonData, ['address', 'regType', 'regId']);

        $address = readJson('address', $payload);
        $regType = readJson('regType', $payload);
        $regId = readJson('regId', $payload);

        $regId = \decryptData($regId);
        // Insert / Update into tmp regis
        $queryUp = QueryBuilder::table('tmp_registration')->execQuery('UPDATE tmp_registration SET address = ?, reg_type = ? WHERE reg_id = ?', [$address, 'customer', $regId]);

        if (false === $queryUp) {
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

        // Make sure phone number not already used
        $phoneTmp = TmpRegistration::select(['phone_number'])->where('reg_id', '=', $regId)->first();
        $phone = $phoneTmp->phone_number;
        // \App\Core\Support\Log::debug($phone, 'RegCustomerController.finish.phone');
        $phoneExists = QueryBuilder::table('customers')->select(['phone_number'])->where('phone_number', '=', $phone)->first();
        // \App\Core\Support\Log::debug($phoneExists, 'RegCustomerController.finish.phoneExists');
        if ($phoneExists) {
            $errors = [
                'busy' => ['Phone number already registred.']
            ];
            return endResponse(
                $this->getOutput(false, 422, [
                   $errors
                ]),
                422
            );
        }

        // Select Insert into table customers
        $queryMove = QueryBuilder::table('customers')->execQuery('INSERT INTO customers (full_name, phone_number, pin, address, agree_tc, allow_enabled_location, allow_notification, fcm_token) SELECT full_name, phone_number, pin, address, agree_tc, allow_enabled_location, allow_notification, fcm_token FROM tmp_registration WHERE reg_id = ? LIMIT 1', [$regId]);

        if (false === $queryMove) {
            $errors = [
                'busy' => ['System busy, please try again in few moments.'],
            ];
            return endResponse(
                $this->getOutput(false, 400, [
                   $errors
                ]),
                400
            );
        } else {
            // Clean tmp table
            QueryBuilder::table('tmp_registration')->execQuery('DELETE FROM tmp_registration WHERE reg_id = ?', [$regId]);
        }

        return endResponse(
            $this->getOutput(true, 201, [
                'regId' => encryptData($regId),
            ]),
            201
        );
    }
}
