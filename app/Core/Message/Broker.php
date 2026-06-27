<?php

namespace App\Core\Message;

use App\Core\Support\Config;
use Exception;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPIOException;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Message\AMQPMessage;

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
     * @param  string|null $message
     * @param  string|null $method
     *
     * @return bool Mengembalikan true jika berhasil, false jika gagal koneksi
     */
    public static function sendMessage($message = null, $method = null)
    {
        if (is_null($message)) {
            return false;
        }

        if (self::$driver === 'rabbitmq') {
            try {
                if (is_null($method)) {
                    self::sendMessageRabbitMq($message);
                } else {
                    self::sendMessageRabbitMq($message, $method);
                }
                return true;
            } catch (AMQPIOException $e) {
                // Tangkap jika terjadi kegagalan soket jaringan/koneksi putus
                self::logError("RabbitMQ Connection Error (IO): " . $e->getMessage());
                // Anda bisa menambahkan logika fallback ke sini, misal disimpan ke database lokal dulu
                return false;
            } catch (AMQPRuntimeException $e) {
                // Tangkap jika ada error runtime dari RabbitMQ lainnya
                self::logError("RabbitMQ Runtime Error: " . $e->getMessage());
                return false;
            } catch (\Throwable $e) {
                // Tangkap error tidak terduga lainnya agar aplikasi tidak Fatal Error
                self::logError("RabbitMQ Unexpected Error: " . $e->getMessage());
                return false;
            }
        }

        return false;
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
        // list($queue_name, , ) = $channel->queue_declare("", false, false, true, false);
        [$queue_name, , ] = $channel->queue_declare("", false, false, true, false);

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

    protected static function logError($message)
    {
        if (config("app.debug")) {
            \write_log("error", ["message" => $message], "Message.Broker");
        }
        echo "[!] " . $message . PHP_EOL;
    }
}
