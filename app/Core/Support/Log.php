<?php

namespace App\Core\Support;

use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FirePHPHandler;
use Monolog\Formatter\LineFormatter;

/**
 * Handle all the stuff related to session.
 */
class Log
{

    protected static $logname = 'app_logger';

    protected static $logdir = __DIR__.'/../../../storage/logs/';

    public function __construct()
    {
        // $this->logdir = __DIR__.'/../../../storage/logs/';

        // dd($this->logdir);

        

    }

    public static function getLogdir()
    {
        return self::$logdir;
    }

    public static function info($logs, $singgle=true)
    {
        $logfile = self::getLogdir().'app_info.log';

        if(!$singgle) {
            $renamed = 'app_info_'.date('d-m-Y').'.log';
            $logfile = self::getLogdir().$renamed;
        }


        // Define the desired date format
        $dateFormat = "Y-m-d H:i:s"; // Example: "2025-09-05 17:03:00"

        // Define the output format for the log message, including the datetime placeholder
        $output = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";

        // Create a LineFormatter instance with the custom output and date format
        $formatter = new LineFormatter($output, $dateFormat);

        // Create a StreamHandler to log to a file
        $streamHandler = new StreamHandler($logfile, Logger::DEBUG);

        // Set the formatter for the handler
        $streamHandler->setFormatter($formatter);

        // Create the logger
        $logger = new Logger(self::$logname);
        // Now add some handlers
        $logger->pushHandler($streamHandler);
        $logger->pushHandler(new FirePHPHandler());

        // You can now use your logger
        $logs = self::__formatedString($logs);
        $logger->info($logs);
    }

    private static function __formatedString($logs) 
    {
        if(is_array($logs) || is_object($logs)) 
            $logs = json_encode($logs);

        return $logs;
    }
}