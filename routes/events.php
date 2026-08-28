<?php

use App\Events\OrderPlacedEvent;
use App\Listeners\InventoryUpdateListener;
use App\Listeners\PaymentProcessingListener;
use App\Listeners\EmailNotificationListener;

return [
    /*
    |--------------------------------------------------------------------------
    | Event Listener Mappings
    |--------------------------------------------------------------------------
    */

    // Using key string | constant NAME
    OrderPlacedEvent::NAME => [
        InventoryUpdateListener::class,
        PaymentProcessingListener::class,
        EmailNotificationListener::class,
    ],

    // OR use the FQCN (Fully Qualified Class Name) of the Event
    OrderPlacedEvent::class => [
        InventoryUpdateListener::class,
        PaymentProcessingListener::class,
    ],
];
