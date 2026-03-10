<?php

namespace App\Controllers\Api;

use App\Core\Http\{Request, Response};
use App\Core\Message\FirebaseCloudMessaging;
use App\Core\Validation\Validator;
use App\Core\Database\QueryBuilder;
use App\Core\Database\Model;
use App\Models\User;
use App\Models\Role;
use Exception;

// // Queue
// use Amp;
// use Amp\Future;
// use function Amp\Future\awaitAnyN;
// use function Amp\async;
// use Amp\CompositeException;
// use Amp\MultiReasonException;
// use Amp\Http\Client\HttpClientBuilder;
// use Amp\Http\Client\Request as clientRequest;

// AI
use App\Neuron\BpAgent;
use App\Neuron\Output\Person;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Observability\AgentMonitoring;

// Ollama
use App\Neuron\OllamaExec;

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

    public function neuronAi(Request $request, Response $response)    
    {
        try {
            if($request->has('prompt') && $request->prompt !== '') {

                // // Menggunakan Neuon AI
                // $responseAi = BpAgent::make()->chat(
                //     new UserMessage($request->prompt ?? "Hi, Who are you?")
                // );
        
                // echo $responseAi->getContent();
                // // I'm a friendly AI Agent built with Neuron, how can I help you today?


                // Using OllamaExec
                $selectedModel = 'default-chat';
                $model = new OllamaExec($selectedModel);
                if (!$model->checkModelExists()) {
                    echo "Error: Model belum terpasang di sistem.";
                    exit;
                }

                // Calling the Wrapper class we created earlier
                $prompt = trim($request->prompt);
                $response = $model->ask($prompt, $selectedModel);

                // If the response is an array (error from cURL)
                if (is_array($response)) {
                    echo "There is an error: " . $response['message'];
                } else {
                    // Returns the AI's answer text
                    echo $response;
                }

            } else {
                // Talk to the agent requiring the structured output
                $person = BpAgent::make()->structured(
                    new UserMessage("I'm John and I want a pizza at st. James Street 00560!, Tags: jhon, james street, pizza"),
                    Person::class
                );

                echo $person->name.' like '.$person->preference.'. Address: '.$person->address->street . PHP_EOL;
                // John like pizza. Address: st.James Street
                echo \json_encode($person->tags);
            }
            
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            echo "File: " . $e->getFile() . "\n";
            echo "Line: " . $e->getLine() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString();
        }
        
        exit;

        // dd([$responseAi], true);

        // \App\Core\Support\Log::debug($request->all(), 'TestingController.index.request');
        // return endResponse(
        //     $this->getOutput(true, 200, [
        //         'info' => 'This index path',
        //         'request'=> $request->all(),
        //     ], 'TestingController'), 
        //     200
        // );
    }

    public function queue()
    {

        // A helper function to simulate an asynchronous task that might succeed or fail
        function simulatedAsyncRequest(string $url, bool $shouldSucceed): Future
        {
            $httpClient = HttpClientBuilder::buildDefault();

            return async(function () use ($url, $shouldSucceed, $httpClient): string {
                // In a real app, this would be an I/O operation (e.g., HTTP request)
                if (!$shouldSucceed) {
                    throw new \Exception("Failed to fetch $url");
                }

                $r = $httpClient->request(new clientRequest($url, 'HEAD'));

                $log = printf(
                    "%s | HTTP/%s %d %s\n",
                    $url,
                    $r->getProtocolVersion(),
                    $r->getStatus(),
                    $r->getReason()
                );
                \App\Core\Support\Log::debug([$url, $r->getProtocolVersion(), $r->getStatus(), $r->getReason()], 'TestingController.queue.simulatedAsyncRequest.r');
                return $log;
            });
        }

        // Assume some functions that return Futures which might succeed or fail
        function createFutures(): array {
            return [
                'future1' => Future::error(new Exception('Reason 1')),
                'future2' => Future::complete('Success 2'),
                'future3' => Future::error(new Exception('Reason 3')),
                'future4' => Future::complete('Success 4'),
            ];
        }

        // Prepare an array of futures: 2 will succeed, 2 will fail
        $futures = [
            'google' => simulatedAsyncRequest('https://www.google.com', true),
            'bing' => simulatedAsyncRequest('https://www.bing.com', true),
            'yahoo' => simulatedAsyncRequest('https://www.yahoo.com', true),
            'microsoft' => simulatedAsyncRequest('https://www.microsoft.com', true),
        ];


        // We want 3 successful results, but only 2 are available.
        $count = 4; 
        try {
            // Await 3 successful futures
            $successfulResults = awaitAnyN($count, $futures);

            echo "Successfully retrieved $count results:\n";
            foreach ($successfulResults as $key => $value) {
                echo "* $key: $value\n";
            }

            // Await any futures. 
            // This example will succeed and return an array with 'Success 2' and 'Success 4'.
            $results = Future\awaitAnyN($count, createFutures());
            print_r($results);
        } catch (CompositeException $e) {
            echo "Could not complete $count tasks successfully.\n";
            
            // Use getReasons() to retrieve an array of all specific exceptions that occurred
            $reasons = $e->getReasons();
            $failCount = count($reasons);
            $successCount = $count - $failCount;
            echo "Success $successCount tasks, Failed $failCount tasks.\n";
            echo "Reasons for failure:\n";
            
            foreach ($reasons as $key => $reason) {
                // $key corresponds to the original key in the $futures array
                echo " - [$key]: " . $reason->getMessage() . "\n";
            }
        
        } catch (MultiReasonException $e) {
            echo "Caught a MultiReasonException: " . $e->getMessage() . "\n";
        
            // Retrieve the array of individual exceptions
            $reasons = $e->getReasons();
        
            echo "Individual reasons:\n";
            foreach ($reasons as $index => $reason) {
                if ($reason instanceof Exception) {
                    echo "  Reason " . ($index + 1) . ": " . $reason->getMessage() . "\n";
                }
            }
        } catch (Exception $e) {
            // If any one of the requests fails the combo will fail
            echo "Caught a general Exception: " . $e->getMessage() . "\n";
        }
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

