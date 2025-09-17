<?php

// Listeners are classes which listen to a certain event and carry out a specific task. Now, create the listeners to update the inventory, to send email confirmation and to initiate the payment processing.

namespace App\Listeners;  
  
use App\Events\OrderPlacedEvent;  
  
/**  
 * Listener for updating inventory when an order is placed.  
 */  
class InventoryUpdateListener {  
    /**  
     * Handles the event of an order being placed.  
     *  
     * @param OrderPlacedEvent $event The event object.  
     */  
    public function onOrderPlaced(OrderPlacedEvent $event) {  
        // Logic to update inventory  
        $order = $event->getOrder();  
        echo "Inventory updated for order: " . $order['id'] . PHP_EOL;  
    }  
}
