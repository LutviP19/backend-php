<?php

namespace App\Core\Support;

use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FirePHPHandler;

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

        // Create the logger
        $logger = new Logger(self::$logname);
        // Now add some handlers
        $logger->pushHandler(new StreamHandler($logfile, Level::Debug));
        $logger->pushHandler(new FirePHPHandler());

        // You can now use your logger
        $logs = self::__formatedString($logs);
        $logger->info($logs);
    }

    private static function __formatedString($logs) 
    {
        if(is_array($logs)) 
            $logs = json_encode($logs);

        return $logs;
    }
}