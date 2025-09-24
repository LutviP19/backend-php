<?php

namespace App\Core\Message;

use App\Core\Support\Config;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Exception;

/**
 * Broker class
 * @author Lutvi <lutvip19@gmail.com>
 */
class Broker
{
    protected static $driver;

    public function __construct()
    {
        self::$driver = Config::get('default_mb');
    }

    /**
     * sendMessage function
     *
     * @param  [string] $message
     * @param  [typstringe] $method
     *
     * @return void
     */
    public static function sendMessage($message = null, $method = null)
    {
        if (is_null($message)) {
            return;
        }

        if (self::$driver === 'rabbitmq') {

            if (is_null($message)) {
                throw new Exception('Message is empty.');
            }

            if (is_null($method)) {
                self::sendMessageRabbitMq($message);
            } else {
                self::sendMessageRabbitMq($message, $method);
            }
        }

    }

    /**
     * getMessage function
     *
     * @param  [callable] $callback
     * @param  [string] $method
     *
     * @return void
     */
    public static function getMessage($callback = null, $method = null)
    {
        if (is_null($callback)) {
            return;
        }

        if (self::$driver === 'rabbitmq') {

            if (!is_callable($callback)) {
                throw new Exception('Invalid callback function.');
            }

            if (is_null($method)) {
                self::getMessageRabbitMq($callback);
            } else {
                self::getMessageRabbitMq($callback, $method);
            }
        }

    }

    /**
     * sendMessageRabbitMq function
     *
     * @param  [string] $message
     * @param  string $method
     *
     * @return void
     */
    private static function sendMessageRabbitMq($message, $method = 'fanout')
    {
        $default_mb = self::$driver;
        $queueName = Config::get("broker.{$default_mb}.queue_name");

        $connection = new AMQPStreamConnection(Config::get("broker.{$default_mb}.host"), Config::get("broker.{$default_mb}.port"), Config::get("broker.{$default_mb}.username"), Config::get("broker.{$default_mb}.password"));

        $channel = $connection->channel();
        $channel->exchange_declare($queueName, $method, false, false, false);

        try {
            $msg = new AMQPMessage($message);
            $channel->basic_publish($msg, $queueName);
        } catch (\Throwable $exception) {
            throw new Exception($exception->getMessage());
        }

        $channel->close();
        $connection->close();
    }

    /**
     * getMessageRabbitMq function
     *
     * @param  [callable] $callback
     * @param  string $method
     *
     * @return void
     */
    private static function getMessageRabbitMq($callback, $method = 'fanout')
    {
        if (!is_callable($callback)) {
            throw new Exception('Invalid callback function.');
        }

        $default_mb = self::$driver;
        $queueName = Config::get("broker.{$default_mb}.queue_name");

        $connection = new AMQPStreamConnection(Config::get("broker.{$default_mb}.host"), Config::get("broker.{$default_mb}.port"), Config::get("broker.{$default_mb}.username"), Config::get("broker.{$default_mb}.password"));

        $channel = $connection->channel();
        $channel->exchange_declare($queueName, $method, false, false, false);
        list($queue_name, , ) = $channel->queue_declare("", false, false, true, false);

        $channel->queue_bind($queue_name, $queueName);
        $channel->basic_consume($queue_name, '', false, true, false, false, $callback);

        try {
            $channel->consume();
        } catch (\Throwable $exception) {
            throw new Exception($exception->getMessage());
        }

        $channel->close();
        $connection->close();
    }
}
