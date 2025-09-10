<?php
namespace App\Console\Commands;

use App\Core\Events\Event;
use App\Core\Message\Broker;
use App\Core\Security\Encryption;
use App\Core\Support\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use PhpAmqpLib\Connection\AMQPStreamConnection;



class TestCommand extends Command
{
    protected $id;

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('app:testing')
            ->setDescription('Prints Hello-World!')
            ->setHelp('Demonstration of custom commands created by Symfony Console component.')
            ->addArgument('userid', InputArgument::REQUIRED, 'Pass the userid.');
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->id = $input->getArgument('userid');

        $output->writeln(" [*] Waiting for messages ".$this->id.". To exit press CTRL+C\n");

        // Event Listener
        Event::listen('message.queue', function($body) {
            echo "EventListener[message.queue]: {$body}\n";
        });
        
        $this->getMessageBroker();

        return self::SUCCESS;
    }

    private function getMessageBroker()
    {
        // Consume body
        $callback = function ($msg) {
            $body = decryptData($msg->getBody());

            // Trigger Event
            Event::trigger('message.queue', $body);

            if(isJson($body)) {
                $data = json_decode($body, true);
                $date = date('d-m-Y H:i:s');

                echo ' [x] ', $date, "\n";

                // \App\Core\Support\Log::info($this->id, 'TestCommand.getMessage');
                // \App\Core\Support\Log::info(gettype($data), 'TestCommand.getMessage');
                foreach($data as $key => $val) {
                    // \App\Core\Support\Log::info(gettype($val), 'TestCommand.getMessage');
                    if(is_array($val)) {
                        // Filtered ID
                        if(isset($val['id']) && 
                            $val['id'] == $this->id) {
                            
                            foreach($val as $k => $v) {
                                echo "$k: $v\n";
                            }
                        }
                    }
                    else
                        echo "$key: $val\n";
                }
                
                echo "=====\n";
            }
            else {
                echo ' [x] ', $body, "\n";
                // \App\Core\Support\Log::info('RabbitMQ message received: '.$body, 'TestCommand.getMessage');
            }
        };

        $broker = new Broker();
        $broker->getMessage($callback);
    }

    private function getMessage()
    {
        $default_mb = Config::get('default_mb');

        $queueName = Config::get("broker.{$default_mb}.queue_name");
        // echo $queueName;

        $connection = new AMQPStreamConnection(Config::get("broker.{$default_mb}.host"), Config::get("broker.{$default_mb}.port"), Config::get("broker.{$default_mb}.username"), Config::get("broker.{$default_mb}.password"));

        $channel = $connection->channel();
        $channel->exchange_declare($queueName, 'fanout', false, false, false);
        list($queue_name, ,) = $channel->queue_declare("", false, false, true, false);

        $channel->queue_bind($queue_name, $queueName);

        // Consume body
        $callback = function ($msg) {
            $body = decryptData($msg->getBody());

            if(isJson($body)) {
                $data = json_decode($body, true);
                $date = date('d-m-Y H:i:s');

                echo ' [x] ', $date, "\n";

                // \App\Core\Support\Log::info($this->id, 'TestCommand.getMessage');
                // \App\Core\Support\Log::info(gettype($data), 'TestCommand.getMessage');
                foreach($data as $key => $val) {
                    // \App\Core\Support\Log::info(gettype($val), 'TestCommand.getMessage');
                    if(is_array($val)) {
                        // Filtered ID
                        if(isset($val['id']) && 
                            $val['id'] == $this->id) {
                            
                            foreach($val as $k => $v) {
                                echo "$k: $v\n";
                            }
                        }
                    }
                    else
                        echo "$key: $val\n";
                }
                
                echo "=====\n";
            }
            else {
                echo ' [x] ', $body, "\n";
                // \App\Core\Support\Log::info('RabbitMQ message received: '.$body, 'TestCommand.getMessage');
            }
        };

        $channel->basic_consume($queue_name, '', false, true, false, false, $callback);

        try {
            $channel->consume();
        } catch (\Throwable $exception) {
            echo $exception->getMessage();
        }

        $channel->close();
        $connection->close();
    }
}