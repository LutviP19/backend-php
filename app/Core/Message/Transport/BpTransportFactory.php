<?php

namespace App\Core\Message\Transport;

use Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface;
use Symfony\Component\Messenger\Transport\Sender\SenderInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

/**
 * BpTransportFactory class
 * @author Lutvi <lutvip19@gmail.com>
 */
class BpTransportFactory implements TransportFactoryInterface
{
    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        return new BpTransport(/* ... */);
    }

    public function supports(string $dsn, array $options): bool
    {
        return 0 === strpos($dsn, 'bp-transport://');
    }
}