<?php

namespace App\Core\Message\Transport;

use App\Core\Database\BpDatabase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Component\Uid\Uuid;

/**
 * BpTransport class
 * @author Lutvi <lutvip19@gmail.com>
 */
class BpTransport implements TransportInterface
{
    private SerializerInterface $serializer;

    /**
     * @param BpDatabase $db is used for init core database.
     */
    public function __construct(
        private BpDatabase $db = null,
        ?SerializerInterface $serializer = null,
    ) {
        $this->db = $db ?? new BpDatabase();
        $this->serializer = $serializer ?? new PhpSerializer();
    }

    public function get(): iterable
    {
        // Get a message from "my_queue"
        $row = $this->db->createQuery(
                'SELECT *
                FROM my_queue
                WHERE (delivered_at IS NULL OR delivered_at < :redeliver_timeout)
                AND handled = FALSE'
            )
            ->setParameter('redeliver_timeout', new DateTimeImmutable('-5 minutes'))
            ->getOneOrNullResult();

        if (null === $row) {
            return [];
        }

        $envelope = $this->serializer->decode([
            'body' => $row['envelope'],
        ]);

        return [$envelope->with(new TransportMessageIdStamp($row['id']))];
    }

    public function ack(Envelope $envelope): void
    {
        $stamp = $envelope->last(TransportMessageIdStamp::class);
        if (!$stamp instanceof TransportMessageIdStamp) {
            throw new \LogicException('No TransportMessageIdStamp found on the Envelope.');
        }

        // Mark the message as "handled"
        $this->db->createQuery('UPDATE my_queue SET handled = TRUE WHERE id = :id')
            ->setParameter('id', $stamp->getId())
            ->execute();
    }

    public function reject(Envelope $envelope): void
    {
        $stamp = $envelope->last(TransportMessageIdStamp::class);
        if (!$stamp instanceof TransportMessageIdStamp) {
            throw new \LogicException('No TransportMessageIdStamp found on the Envelope.');
        }

        // Delete the message from the "my_queue" table
        $this->db->createQuery('DELETE FROM my_queue WHERE id = :id')
            ->setParameter('id', $stamp->getId())
            ->execute();
    }

    public function send(Envelope $envelope): Envelope
    {
        $encodedMessage = $this->serializer->encode($envelope);
        $uuid = (string) Uuid::v4();
        // Add a message to the "my_queue" table
        $this->db->createQuery(
                'INSERT INTO my_queue (id, envelope, delivered_at, handled)
                VALUES (:id, :envelope, NULL, FALSE)'
            )
            ->setParameters([
                'id' => $uuid,
                'envelope' => $encodedMessage['body'],
            ])
            ->execute();

        return $envelope->with(new TransportMessageIdStamp($uuid));
    }
}
