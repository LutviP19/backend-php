<?php
namespace App\Console\Commands;


use App\Core\Support\Config;
use App\Core\Support\Session;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\ProgressBar;
 
class InfoCommand extends Command
{
    protected $id;

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('app:info')
            ->setDescription('Get info of the application setup.')
            ->setHelp('Allows you to get the application info. Pass the --groups parameter to get info of specific groups.')
            ->addArgument('userid', InputArgument::REQUIRED, 'Pass the userid.')
            ->addOption(
                    'groups',
                    'g',
                    InputOption::VALUE_OPTIONAL,
                    'Pass the comma separated group names if you don\'t want to show all info.',
                    ''
                );
    }
 
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->id = $input->getArgument('userid');

        if ($input->getOption('groups'))
        {
            $groups = explode(",", $input->getOption('groups'));
            $progressBar = new ProgressBar($output, count($groups));
 
            $progressBar->start();
            
            if (is_array($groups) && count($groups))
            {
                foreach ($groups as $group)
                {
                    sleep(3);
                    $progressBar->advance();
                }
            }
            $progressBar->finish();
        }
        else
        {
            $getInfo = $this->getAppInfo();
            $output->writeln($getInfo);
            $output->writeln('All info of application.');
        }
        $output->writeln('');
        
        return Command::SUCCESS;
    }

    public function getAppInfo($group = [])
    {
        $appName = Config::get('app.name');
        $appUrl = Config::get('app.url');
        $appPath = Config::get('app.path');
        $appEnv = Config::get('app.env');
        $appDebug = Config::get('app.debug') ? 'true' : 'false';
        $appSessionDriver = env('SESSION_DRIVER');
        $appDbDriver = Config::get('default_db');
        $appMbDriver = Config::get('default_mb');
        $appMailerDriver = Config::get('default_mailer');

        $tokenHeaderApi = encryptData(Config::get('app.token'));

        $userId = (int) $this->id;
        $validateClient = new \App\Core\Security\Middleware\ValidateClient($userId, 'id');
        $tokenHeaderClient = $validateClient->generateToken();

        $info = "App Name: {$appName}".PHP_EOL;
        $info .= "App Url: {$appUrl}".PHP_EOL;
        $info .= "App Base Path: {$appPath}".PHP_EOL;
        $info .= "App Environment: {$appEnv}".PHP_EOL;
        $info .= "App Debug status: {$appDebug}".PHP_EOL;
        $info .= "App Session driver: {$appSessionDriver}".PHP_EOL;
        $info .= "App Database driver: {$appDbDriver}".PHP_EOL;
        $info .= "App Message Broker driver: {$appMbDriver}".PHP_EOL;
        $info .= "App Mailer driver: {$appMailerDriver}".PHP_EOL;
        $info .= "App header api token [X-Api-Token]: {$tokenHeaderApi}".PHP_EOL;
        $info .= "User ID: {$userId}".PHP_EOL;
        $info .= "App header client token [X-Client-Token]: {$tokenHeaderClient}".PHP_EOL;

        return $info;
    }
}
