<?php

namespace App\Controllers;

use App\Core\Database\Model;
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
    //controller constructor.
    public function __construct()
    {
        $this->csrf();
    }

    /**
     * Show the home page.
     *
     * @param App\Core\Http\Request $request
     * @param App\Core\Http\Response $response
     * @return void
     */
    public function index(Request $request, Response $response)
    {
        $users = Model::table('users')->select(['*'])->get();
        // dd($users);
        // Session::set('users', generateUlid());
        $server = \in_array($_SERVER['SERVER_PORT'], config('app.ignore_port')) ? "OpenSwoole" : "PHP FPM";

        $this->view('spa.index', ['users' => $users, 'server' => $server]);
    }

    /**
     * Show the home page.
     *
     * @return void
     */
    public function demoSpa(Request $request, Response $response)
    {
        $this->view('spa.pages.main');
    }

}
