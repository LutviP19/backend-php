<?php

// to associate these listeners with Event Dispatcher. Make sure there is an event to be dispatched that the order was placed.

namespace App\Core\Events;

use Symfony\Component\EventDispatcher\EventDispatcher as SymfonyEventDispatcher;  
use App\Events\OrderPlacedEvent;  
use App\Listeners\InventoryUpdateListener;  
use App\Listeners\EmailNotificationListener;  
use App\Listeners\PaymentProcessingListener;  

class EventDispatcher
{

    public function __construct()
    {
        
    }

    public function dispatch()
    {
        /**  
         * Registering event listeners with the Event Dispatcher.  
         */  
        $dispatcher = new SymfonyEventDispatcher();

        $dispatcher->addListener(OrderPlacedEvent::NAME, [new InventoryUpdateListener(), 'onOrderPlaced']);  
        $dispatcher->addListener(OrderPlacedEvent::NAME, [new EmailNotificationListener(), 'onOrderPlaced']);  
        $dispatcher->addListener(OrderPlacedEvent::NAME, [new PaymentProcessingListener(), 'onOrderPlaced']);
    }
}


