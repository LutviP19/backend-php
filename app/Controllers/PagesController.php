<?php

namespace App\Controllers;

use App\Core\Events\Event;
use App\Core\Message\Broker;
use App\Models\User;
use App\Core\Support\Config;
use App\Core\Http\{Request,Response};

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

use Amp\Future;
use function Amp\async;
use function Amp\delay;

class PagesController extends Controller
{

    /**
     * Show the home page.
     * 
     * @param App\Core\Http\Request $request
     * @param App\Core\Http\Response $response
     * @return void
     */
    public function index(Request $request,Response $response)
    {
        $users = (new User())->all();

        $this->view('home', ['users' => $users]);
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
    public function extra()
    {
        // $get =  Request::get('get');
        // $this->view('extra', ['low' => 'lower', 'get' => $get]);

        // Producer
        $date = date('d-m-Y H:i:s');
        $data = [
            ['id' => 1, 'title' => 'Title A', 'contents' => 'The default interactive shell is now zsh.'],
            ['id' => 2, 'title' => 'Title B', 'contents' => 'To update your account to use zsh'],
            ['id' => 3, 'title' => 'Title C', 'contents' => 'For more details, please visit'],
        ];

        $default = 'Testing MQ '.$date;
        $default = config('app.token');
        $default = json_encode($data);

        $message = encryptData($default);

        // sendMessage
        $broker = new Broker();
        $broker->sendMessage($message);

        echo ' [x] Sent: ', decryptData($message), "<br>\r\n";
        echo "Sending message to RabbitMQ: {$message}";

        Event::listen('message.queue', function($param) {
            echo "Event '. $param .' [message.queue]<br>\r\n";
        });

        if(true) {
            Event::trigger('message.queue', $message);
        }

        //===================================== concurrent 

        // $future1 = async(function () {
        //     echo 'Hello ';
        
        //     // delay() is a non-blocking version of PHP's sleep() function,
        //     // which only pauses the current fiber instead of blocking the whole process.
        //     delay(2);
        
        //     echo 'the future! ';
        // });
        
        // $future2 = async(function () {
        //     echo 'World ';
        
        //     // Let's pause for only 1 instead of 2 seconds here,
        //     // so our text is printed in the correct order.
        //     delay(1);
        
        //     echo 'from ';
        // });
        
        // // Our functions have been queued, but won't be executed until the event-loop gains control.
        // echo "Let's start: ";
        
        // // Awaiting a future outside a fiber switches to the event loop until the future is complete.
        // // Once the event loop gains control, it executes our already queued functions we've passed to async()
        // $future1->await();
        // $future2->await();
        
        // echo PHP_EOL;
    }

}
