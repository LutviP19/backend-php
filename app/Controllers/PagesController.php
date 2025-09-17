<?php

namespace App\Controllers;

use App\Core\Support\Session;
use App\Core\Events\Event;
use App\Core\Message\Broker;
use App\Models\User;
use App\Core\Support\Config;
use App\Core\Http\{Request,Response};


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
        Session::set('users', generateUlid());

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

        echo "[x] Sent[$date]: ", decryptData($message), "<br>\r\n";
        echo "Sending message to RabbitMQ: {$message}";

        // Simulate Event with param as array
        Event::listen('message.producer', function($date, $message) {
            echo "Event[message.producer][$date]: $message<br>\r\n";
        });

        if(true) {
            Event::trigger('message.producer', [$date, $message]);
        }
    }

}
