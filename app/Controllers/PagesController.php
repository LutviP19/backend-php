<?php

namespace App\Controllers;

use App\Models\User;
use App\Core\Events\Event;
use App\Core\Http\{Request, Response};
use App\Core\Message\Broker;
use App\Core\Support\Config;
use App\Core\Support\Session;
// Events
use App\Core\Events\EventDispatcher;
use App\Services\OrderService;

use function Amp\async;

class PagesController extends Controller
{
    /**
     * Show the home page.
     *
     * @param App\Core\Http\Request $request
     * @param App\Core\Http\Response $response
     * @return void
     */
    public function index(Request $request, Response $response)
    {
        $users = (new User())->all();
        // Session::set('users', generateUlid());
        $server = \in_array($_SERVER['SERVER_PORT'], config('app.ignore_port')) ? "OpenSwoole" : "PHP FPM";

        $this->view('home', ['users' => $users, 'server' => $server]);
    }

    /**
     * Show the home page.
     *
     * @return void
     */
    public function contact()
    {
        $this->view('contact');
    }

    /**
     * Show the home page.
     *
     * @return void
     */
    public function about()
    {
        $this->view('about');
    }

    /**
     * Show the home page.
     *
     * @return void
     */
    public function dashboard()
    {
        $this->view('dashboard');
    }

    /**
     * Show the home page.
     *
     * @return void
     */
    public function extra()
    {
        $this->include('includes.header');
        echo "<div class='container' style='word-wrap: break-word;'>";

        // $get =  Request::get('get');
        // $this->view('extra', ['low' => 'lower', 'get' => $get]);

        // Producer
        $date = date('d-m-Y H:i:s');
        $data = [
            ['id' => 1, 'title' => 'Title A', 'contents' => 'The default interactive shell is now zsh.'],
            ['id' => 2, 'title' => 'Title B', 'contents' => 'To update your account to use zsh'],
            ['id' => 3, 'title' => 'Title C', 'contents' => 'For more details, please visit'],
        ];

        $default = 'Testing MQ ' . $date;
        $default = config('app.token');
        $default = json_encode($data);

        $message = encryptData($default);

        // sendMessage
        $broker = new Broker();
        $broker->sendMessage($message);

        // \App\Core\Support\Log::debug($_SERVER, 'PagesController.extra.$_SERVER');
        // \App\Core\Support\Log::debug($_COOKIE, 'PagesController.extra.$_COOKIE');

        echo "[x] SESSION_ID: ", \session_id(), "<br>\r\n";
        echo "[x] Sent[$date]: ", decryptData($message), "<br>\r\n";
        echo "Sending message to RabbitMQ: {$message}";

        // Simulate Event with param as array
        Event::listen('message.producer', function ($date, $message) {
            echo "<br>Event[message.producer][$date]: $message<br>\r\n";
        });

        if (true) {
            Event::trigger('message.producer', [$date, $message]);
        }

        /**
         * Main script to place an order and trigger the event-driven process.
         */
        echo "===================================================<br>\r\n";
        echo "Main output to place an order and trigger the event-driven process.<br>\r\n";
        $dispatcher = new EventDispatcher();
        $orderService = new OrderService($dispatcher);

        $order = ['id' => 123, 'items' => ['item1', 'item2']];
        $orderService->placeOrder($order);
        echo "<br>";
        /**
         * END Main script to place an order and trigger the event-driven process.
         */


        // concurrent process
        $index = array_search(1, array_column($data, 'id')) ?: 1; // get index
        if (isset($data[$index]) && ! isset($data['event'])) {
            $output = $this->simulateConcurrent($data[$index]);

            $json = is_array($output) ? json_encode($output) : 'xxx';
            echo "<br>simulateConcurrent-output: {$json}\n" . PHP_EOL;
        }

        echo "</div>";
        $this->include('includes.footer');
    }

    // concurrent
    private function simulateConcurrent($data)
    {
        $output = [];
        $future1 = async(function () use ($data) {
            echo 'Parse key id: ';
            echo "[key:id, value:" . $data['id'] . "]" . PHP_EOL;

            // delay() is a non-blocking version of PHP's sleep() function,
            // which only pauses the current fiber instead of blocking the whole process.
            // delay(1);
            for ($i = 0; $i <= 30; $i++) {

                $counter = $i;
                $this->calculateNthFibonacci($counter);
            }

            return $data['id'] . " counter: {$counter}x";
        });

        $future2 = async(function () use ($data) {
            echo 'Parse key title: ';
            echo "[key:title, value:" . $data['title'] . "]" . PHP_EOL;

            // Let's pause for only 1 instead of 2 seconds here,
            // so our text is printed in the correct order.
            // delay(2);
            for ($i = 0; $i <= 30; $i++) {
                
                $counter = $i;
                $this->calculateNthFibonacci($counter);
            }

            return $data['title'] . " counter: {$counter}x";
        });

        $future3 = async(function () use ($data) {
            echo 'Parse key contents: ';
            echo "[key:contents, value:" . $data['contents'] . "]" . PHP_EOL;

            // Let's pause for only 1 instead of 3 seconds here,
            // so our text is printed in the correct order.
            // delay(3);
            for ($i = 0; $i <= 30; $i++) {

                $counter = $i;
                $this->calculateNthFibonacci($counter);
            }

            return $data['contents'] . " counter: {$counter}x";
        });

        // Our functions have been queued, but won't be executed until the event-loop gains control.
        echo "<br>Let's start non-blocking version: <br>" . PHP_EOL;

        // Awaiting a future outside a fiber switches to the event loop until the future is complete.
        // Once the event loop gains control, it executes our already queued functions we've passed to async()
        $output['id'] = $future1->await();
        $output['title'] = $future2->await();
        $output['contents'] = $future3->await();

        echo PHP_EOL;

        return $output;
    }


    // Simulate currency process
    private function calculateNthFibonacci($n, $max = 30) {
        if ($n <= 1 || $n >= $max) {
            return $n;
        }
        return $this->calculateNthFibonacci($n - 1) + $this->calculateNthFibonacci($n - 2);
    }

}
