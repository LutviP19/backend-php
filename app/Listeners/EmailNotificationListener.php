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
        $from = "noreplay@hello.test, Backend PHP Mailer";
        $subject = "New Order";
        $bodyText = "This is an message for order!";
        $bodyHtml = "<strong>This is an message for order!</strong>";
        $attachment = [BASEPATH.'/DEV.txt', BASEPATH.'/DEMO.txt'];
        $image = [];

        for($i=1; $i <=3; $i++) {
            $to = "your-email-$i@here.test, Test User $i";

            sendEmail($from, $to, $subject, $bodyText, $bodyHtml, $attachment, $image);
        }

        // Send Message Queue
        $message = encryptData(json_encode(['event' => $event::NAME] + $order));
        (new \App\Core\Message\Broker())->sendMessage($message);
    }
}
