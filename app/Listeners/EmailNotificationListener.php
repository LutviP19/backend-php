<?php

// Listeners are classes which listen to a certain event and carry out a specific task. Now, create the listeners to update the inventory, to send email confirmation and to initiate the payment processing.

namespace App\Listeners;

use App\Events\OrderPlacedEvent;

/**
 * Listener for sending email notifications when an order is placed.
 */
class EmailNotificationListener
{
    /**  
     * Handles the event of an order being placed.  
     *  
     * @param OrderPlacedEvent $event The event object. 
     */
    public function onOrderPlaced(OrderPlacedEvent $event)
    {
        // Logic to send email
        $order = $event->getOrder();        
        echo "Email sent for order: " . $order['id'] . PHP_EOL;

        // Send Email
        (new \App\Core\Mailer\Email)->send();

        // Send Message Queue
        $message = encryptData(json_encode(['event' => $event::NAME] + $order));
        (new \App\Core\Message\Broker())->sendMessage($message);
    }
}
