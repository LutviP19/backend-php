<?php

// to associate these listeners with Event Dispatcher. Make sure there is an event to be dispatched that the order was placed.

namespace App\Core\Events;

use Symfony\Component\EventDispatcher\EventDispatcher as SymfonyEventDispatcher;
use App\Listeners\InventoryUpdateListener;
use App\Listeners\EmailNotificationListener;
use App\Listeners\PaymentProcessingListener;

class EventDispatcher
{
    public function dispatch($event, $method)
    {
        $eventName = $event::NAME;

        /**
         * Registering event listeners with the Event Dispatcher.
         */
        $dispatcher = new SymfonyEventDispatcher();

        // onOrderPlaced
        if ($eventName === 'order.placed') {
            $dispatcher->addListener($eventName, [
                                                    (new InventoryUpdateListener())->{$method}($event),
                                                    (new PaymentProcessingListener())->{$method}($event),
                                                    (new EmailNotificationListener())->{$method}($event),
                                                ]);
        }
    }
}
