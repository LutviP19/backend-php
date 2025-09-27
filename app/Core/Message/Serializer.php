<?php

namespace App\Core\Message\Serializer;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

/**
 * MessageWithTokenDecoder class
 * @author Lutvi <lutvip19@gmail.com>
 */
class MessageWithTokenDecoder implements SerializerInterface
{
    public function decode(array $encodedEnvelope): Envelope
    {
        try {
            // parse the data you received with your custom fields
            $data = $encodedEnvelope['data'];
            $data['token'] = $encodedEnvelope['token'];

            // other operations like getting information from stamps
        } catch (\Throwable $throwable) {
            // wrap any exception that may occur in the envelope to send it to the failure transport
            return new Envelope($throwable);
        }

        return new Envelope($data);
    }

    public function encode(Envelope $envelope): array
    {
        // this decoder does not encode messages, but you can implement it by returning
        // an array with serialized stamps if you need to send messages in a custom format
        throw new \LogicException('This serializer is only used for decoding messages.');
    }
}