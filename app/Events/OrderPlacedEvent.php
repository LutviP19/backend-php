<?php

// for events and create the event order process placement.

namespace App\Events;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class representing the event triggered when an order is placed.
 */
class OrderPlacedEvent extends Event
{
    public const NAME = 'order.placed';

    protected $order;

    /**
     * Constructor to initialize the order data.
     *
     * @param array $order The order details.
     */
    public function __construct($order)
    {
        $this->order = $order;
    }

    /**
     * Gets the order details.
     *
     * @return array The order details.
     */
    public function getOrder()
    {
        return $this->order;
    }
}
