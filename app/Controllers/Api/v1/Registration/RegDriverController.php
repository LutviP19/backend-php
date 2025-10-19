<?php

namespace App\Controllers\Api\v1\Registration;

use App\Core\Database\QueryBuilder;
use App\Models\Driver;
use App\Models\TmpRegistration;
use App\Controllers\Api\ApiController;
use App\Core\Http\{Request, Response};
use App\Core\Validation\Validator;

/*
*   Registration Driver
* 
*/
class RegDriverController extends ApiController
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
            'vehicle_type'  => 'required|string|min:3|max:50',
            'vehicle_number'  => 'required|string|min:3|max:20',
            'license_number'  => 'required|string|min:3|max:50',
            'congregation'  => 'required|string|min:3|max:50',
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
            'vehicle_type' => 'trim|sanitize_string',
            'vehicle_number' => 'trim|sanitize_string',
            'license_number' => 'trim|sanitize_string',
            'congregation' => 'trim|sanitize_string',
            'phone'  => 'trim',
        ]);
        // Sanitize Input
        $payload = $this->filter->sanitize($this->jsonData, ['name','vehicle_type','vehicle_number','license_number','congregation', 'phone']);
        // Parse JSON
        $name = readJson('name', $payload);
        $vehicle_type = readJson('vehicle_type', $payload);
        $vehicle_number = readJson('vehicle_number', $payload);
        $license_number = readJson('license_number', $payload);
        $congregation = readJson('congregation', $payload);
        $phone = readJson('phone', $payload);
        // Check phone number already used
        $phoneExists = Driver::select()->where('phone_number', '=', $phone)->first();
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
        $regId = QueryBuilder::table('tmp_registration')->execQuery('REPLACE INTO tmp_registration (full_name, phone_number, vehicle_type, vehicle_number, license_number, congregation, reg_type) VALUES (?, ?, ?, ?, ?, ?, ?)', [$name, $phone, $vehicle_type, $vehicle_number, $license_number, $congregation, 'driver'], true);

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
        $query = QueryBuilder::table('tmp_registration')->execQuery('UPDATE tmp_registration SET allow_notification = ?, reg_type = ? WHERE reg_id = ?', [$allow_notification, 'driver', $regId]);

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
        $query = QueryBuilder::table('tmp_registration')->execQuery('UPDATE tmp_registration SET pin = ?, reg_type = ? WHERE reg_id = ?', [$pin, 'driver', $regId]);

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
        $query = QueryBuilder::table('tmp_registration')->execQuery('UPDATE tmp_registration SET agree_tc = ?, reg_type = ? WHERE reg_id = ?', [$agree_tc, 'driver', $regId]);

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
        // \App\Core\Support\Log::debug($regId, 'RegCustomerController.step2.readJson.$regId');

        $regId = \decryptData($regId);
        // Insert / Update into tmp regis
        $query = QueryBuilder::table('tmp_registration')->execQuery('UPDATE tmp_registration SET allow_enabled_location = ?, reg_type = ? WHERE reg_id = ?', [$allow_enabled_location, 'driver', $regId]);

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
        // \App\Core\Support\Log::debug($this->jsonData, 'RegCustomerController.step2.$this->jsonData');

        // Sanitize Input
        $payload = $this->filter->sanitize($this->jsonData, ['address', 'regType', 'regId']);
        // \App\Core\Support\Log::debug($payload, 'RegCustomerController.step2.sanitize.$payload');

        $address = readJson('address', $payload);
        $regType = readJson('regType', $payload);
        $regId = readJson('regId', $payload);

        $regId = \decryptData($regId);
        // \App\Core\Support\Log::debug($regId, 'RegCustomerController.step2.$regId');
        // Insert / Update into tmp regis
        $queryUp = QueryBuilder::table('tmp_registration')->execQuery('UPDATE tmp_registration SET address = ?, reg_type = ? WHERE reg_id = ?', [$address, 'driver', $regId]);

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
        $phoneExists = QueryBuilder::table('drivers')->select(['phone_number'])->where('phone_number', '=', $phone)->first();
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

        // Select Insert into table drivers
        $queryMove = QueryBuilder::table('drivers')->execQuery('INSERT INTO drivers (full_name, phone_number, pin, address, agree_tc, allow_enabled_location, allow_notification, vehicle_type, vehicle_number, license_number, congregation, fcm_token) SELECT full_name, phone_number, pin, address, agree_tc, allow_enabled_location, allow_notification, vehicle_type, vehicle_number, license_number, congregation, fcm_token FROM tmp_registration WHERE reg_id = ? LIMIT 1', [$regId]);

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
