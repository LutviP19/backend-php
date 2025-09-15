<?php
namespace App\Console\Commands;

use App\Core\Events\Event;
use App\Core\Message\Broker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Amp\Future;
use function Amp\async;
use function Amp\delay;


class TestCommand extends Command
{
    protected $id;
    protected $eventName;

    public function __construct()
    {
        parent::__construct();

        $this->eventName = 'message.queue';
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

        $output->writeln(" [*] Waiting for messages userid:".$this->id.". To exit press CTRL+C\n");

        // Event Listener
        Event::listen($this->eventName, function($body) {
            echo "EventListener[{$this->eventName}]: {$body}\n".PHP_EOL;;

            // concurrent process
            $data = json_decode($body, true);
            $index = array_search($this->id, array_column($data, 'id')); // get index
            $output = $this->simulateConcurrent($data[$index]);

            $json = is_array($output) ? json_encode($output) : 'xxx';
            echo "simulateConcurrent-output: {$json}\n".PHP_EOL;;
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
            Event::trigger($this->eventName, $body);

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

        // getMessage
        $broker = new Broker();
        $broker->getMessage($callback);
    }

    // concurrent
    private function simulateConcurrent($data)
    {
        $output = [];
        $future1 = async(function () use($data) {
            echo 'Parse key id: ';
            echo "[key:id, value:".$data['id']."]".PHP_EOL;
        
            // delay() is a non-blocking version of PHP's sleep() function,
            // which only pauses the current fiber instead of blocking the whole process.
            // delay(1);
            for($i=0; $i <= 10000; $i++)
                $counter = $i;
        
            return $data['id']." counter: {$counter}x";;
        });
        
        $future2 = async(function () use($data) {
            echo 'Parse key title: ';
            echo "[key:title, value:".$data['title']."]".PHP_EOL;
        
            // Let's pause for only 1 instead of 2 seconds here,
            // so our text is printed in the correct order.
            // delay(2);
            for($i=0; $i <= 2000; $i++)
                $counter = $i;
        
            return $data['title']." counter: {$counter}x";;
        });

        $future3 = async(function () use($data) {
            echo 'Parse key contents: ';
            echo "[key:contents, value:".$data['contents']."]".PHP_EOL;
        
            // Let's pause for only 1 instead of 3 seconds here,
            // so our text is printed in the correct order.
            // delay(3);
            for($i=0; $i <= 15000; $i++)
                $counter = $i;

            return $data['contents']." counter: {$counter}x";
        });
        
        // Our functions have been queued, but won't be executed until the event-loop gains control.
        echo "Let's start non-blocking version: ".PHP_EOL;
        
        // Awaiting a future outside a fiber switches to the event loop until the future is complete.
        // Once the event loop gains control, it executes our already queued functions we've passed to async()
        $output['id'] = $future1->await();
        $output['title'] = $future2->await();
        $output['contents'] = $future3->await();

        echo PHP_EOL;

        return $output;
    }

}