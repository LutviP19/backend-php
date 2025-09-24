<?php

namespace App\Core\Events;

use Roolith\Event\Event as RoolithEvent;

/**
 * Event class
 * @author Lutvi <lutvip19@gmail.com>
 */
class Event extends RoolithEvent
{
}

// https://github.com/im4aLL/roolith-event/tree/master

// // Usage
// // ==================================

// Event::listen('login', function(){
//     echo 'Event user login fired! <br>';
// });

// $user = new User();

// if($user->login()) {
//     Event::trigger('login');
// }

// // Usage with param
// // ==================================

// Event::listen('logout', function($param){
//     echo 'Event '. $param .' logout fired! <br>';
// });

// if($user->logout()) {
//     Event::trigger('logout', 'user');
// }


// // Usage with param as array
// // ==================================

// Event::listen('updated', function($param1, $param2){
//     echo 'Event ('. $param1 .', '. $param2 .') updated fired! <br>';
// });

// if($user->updated()) {
//     Event::trigger('updated', ['param1', 'param2']);
// }
