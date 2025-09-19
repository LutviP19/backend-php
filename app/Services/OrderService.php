<?php

// when a new order is created, the event should be dispatched.

namespace App\Services;

use App\Events\OrderPlacedEvent;  
  
/**  
 * Service class for handling order-related operations.  
 */  
class OrderService {  
    protected $dispatcher;  
  
    /**  
     * Constructor to initialize the event dispatcher.  
     *  
     * @param EventDispatcher $dispatcher The event dispatcher.  
     */  
    public function __construct($dispatcher) {  
        $this->dispatcher = $dispatcher;  
    }  
  
    /**  
     * Places an order and dispatches the order placed event.  
     *  
     * @param array $order The order details.  
     */  
    public function placeOrder($order) {  
        // Logic to place order  
        echo "Order placed: " . $order['id'] . PHP_EOL;  
  
        // Dispatch event  
        $event = new OrderPlacedEvent($order);  
        $this->dispatcher->dispatch($event, 'onOrderPlaced');  
    }
}

