<?php
namespace App\Console\Commands;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use App\Core\Security\Encryption;


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
        // $output->writeln([
        //     'User Creator',
        //     '============',
        //     '',
        // ]);
    
        // $output->writeln('Username: '.$input->getArgument('username'));
        // return Command::SUCCESS;

        $this->id = $input->getArgument('userid');

        $output->writeln(" [*] Waiting for messages ".$this->id.". To exit press CTRL+C\n");
        
        $this->getMessage();

        return self::SUCCESS;
    }

    private function getMessage()
    {
        $queueName = 'mvc_queue';

        $connection = new AMQPStreamConnection('127.0.0.1', '5672', 'guest', 'guest');
        $channel = $connection->channel();
        // $channel->exchange_declare($queueName, 'fanout', false, false, false);
        list($queue_name, ,) = $channel->queue_declare("", false, false, true, false);

        $channel->queue_bind($queue_name, $queueName);

        // Consume body
        $callback = function ($msg) {
            $body = decryptData($msg->getBody());

            if(isJson($body)) {
                $data = json_decode($body, true);
                $date = date('d-m-Y H:i:s');

                echo ' [x] ', $date, "\n";

                // \App\Core\Support\Log::info($this->id);
                // \App\Core\Support\Log::info(gettype($data));
                foreach($data as $key => $val){
                    // \App\Core\Support\Log::info(gettype($val));
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
                // \Log::info('RabbitMQ artisan message received: '.$msg->getBody());
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