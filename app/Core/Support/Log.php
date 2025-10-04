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
    // Define the logger name
    protected static $logname = 'backend-php';

    // Define the logs directory path
    protected static $logdir = __DIR__ . '/../../../storage/logs/';

    // Define the desired date format
    protected static $dateFormat = "Y-m-d H:i:s"; // Example: "2025-09-05 17:03:00"

    public function __construct()
    {
        // $this->logdir = __DIR__.'/../../../storage/logs/';

        // dd($this->logdir);
    }

    public static function getLogdir()
    {
        return self::$logdir;
    }

    public static function info($logs, $modul = '', $single = true)
    {
        self::saveLog('info', $logs, $modul, $single);
    }

    public static function error($logs, $modul = '', $single = true)
    {
        self::saveLog('error', $logs, $modul, $single);
    }

    public static function debug($logs, $modul = '', $single = true)
    {
        self::saveLog('debug', $logs, $modul, $single);
    }

    protected static function saveLog($type = 'info', $logs = [], $modul = '', $single = true)
    {
        $logfile = self::getLogdir() . 'app_' . $type . '.log';

        if (!$single) {
            $renamed = 'app_' . $type . '_' . date('d-m-Y') . '.log';
            $logfile = self::getLogdir() . $renamed;
        }

        // Define the output format for the log message, including the datetime placeholder
        $output = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";

        // Create a LineFormatter instance with the custom output and date format
        $formatter = new LineFormatter($output, self::$dateFormat);

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

        if ($type === 'info') {
            $logger->info(!empty($modul) ? "[$modul]" . $logs : $logs);
        } elseif ($type === 'error') {
            $logger->error(!empty($modul) ? "[$modul]" . $logs : $logs);
        } else {
            $logger->debug(!empty($modul) ? "[$modul]" . $logs : $logs);
        }
    }

    private static function __formatedString($logs)
    {
        $type = gettype($logs);
        
        if (is_array($logs) || is_object($logs)) {
            $logs = json_encode($logs, JSON_UNESCAPED_SLASHES);
        }
        if(\is_bool($logs)) {
            return $logs ?"true":"false";
        }

        $logs = "[{$type}]:" . $logs;

        return $logs;
    }
}
