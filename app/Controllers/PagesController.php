<?php

namespace App\Controllers;

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
        $this->view('home');
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
        $message = 'Testing MQ '.$date;

        $connection = new AMQPStreamConnection('127.0.0.1', '5672', 'guest', 'guest');
        $channel = $connection->channel();

        $channel->exchange_declare('mvc_queue', 'fanout', false, false, false);

        $msg = new AMQPMessage($message);
        $channel->basic_publish($msg, 'mvc_queue');
        echo ' [x] Sent: ', $message, "<br>\r\n";

        $channel->close();
        $connection->close();

        echo "Sending message to RabbitMQ: {$message}";

        //=====================================

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
