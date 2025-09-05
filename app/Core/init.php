<?php

//Starting the session will be the first we do.
ini_set('session.save_path', __DIR__.'/../../storage/framework/sessions');
session_start();

/* ----------------------------- Default settings START -------------------------------- */

//prettify the errors.
ini_set("html_errors", 1);
ini_set("error_prepend_string", "<pre style='color: #333; font-face:monospace; font-size:14px;'>");
ini_set("error_append_string ", "</pre>");

date_default_timezone_set("Asia/Jakarta");
/* ----------------------------- Default settings END -------------------------------- */

/**
 * Bootstrap the Application
 */

use App\Core\Support\{App,Session};
use App\Core\Http\{Router,Request};
use App\Core\Validation\MessageBag;

// Looing for .env at the root directory
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__.'/../..');
$dotenv->load();

//register configuration to the app.
App::register('config',require __DIR__.'/../../config/app.php');

/**
 * Register MessageBag with all the validation errors 
 * from session to the App container/registry so we
 * can use them later.
 */
$messageBag = new MessageBag(new Session);
$messageBag->setMessages(Session::flash('errors'));
App::register('errors',$messageBag);

//Call the appropriate route.
$output = Router::load(__DIR__.'/../../routes/routes.php')
    ->dispatch(Request::uri(),Request::method());

//For requests that expect json results.
if(Request::isJsonRequest() && is_string($output)){
    echo $output;
}

/**
 * We need to call this method after we return the output
 * and that way we can save the current uri and use it in
 * the next request as the previous uri.
 */
Session::setPreviousUri(Request::uri());