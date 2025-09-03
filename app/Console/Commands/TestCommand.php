<?php
namespace App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use PhpAmqpLib\Connection\AMQPStreamConnection;



class TestCommand extends Command
{
    protected function configure()
    {
        $this->setName('app:testing')
            ->setDescription('Prints Hello-World!')
            ->setHelp('Demonstration of custom commands created by Symfony Console component.')
            ->addArgument('username', InputArgument::REQUIRED, 'Pass the username.');
    }


    public function __invoke(InputInterface $input, OutputInterface $output): int
    {
        // $output->writeln([
        //     'User Creator',
        //     '============',
        //     '',
        // ]);
    
        // $output->writeln('Username: '.$input->getArgument('username'));
    
        // return Command::SUCCESS;

        $queueName = 'mvc_queue';

        $connection = new AMQPStreamConnection('127.0.0.1', '5672', 'guest', 'guest');
        $channel = $connection->channel();
        $channel->exchange_declare($queueName, 'fanout', false, false, false);
        list($queue_name, ,) = $channel->queue_declare("", false, false, true, false);

        $channel->queue_bind($queue_name, $queueName);

        $output->writeln(" [*] Waiting for messages ".$input->getArgument('username').". To exit press CTRL+C\n");

        // Consume body
        $callback = function ($msg) {
            // $output->writeln($msg->getBody());
            echo ' [x] ', $msg->getBody(), "\n";

            
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