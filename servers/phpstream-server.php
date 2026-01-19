<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use PHPStreamServer\Core\Server;
use PHPStreamServer\Core\Worker\WorkerProcess;
use PHPStreamServer\Plugin\Scheduler\SchedulerPlugin;
use PHPStreamServer\Plugin\Scheduler\Worker\PeriodicProcess;

$server = new Server();

$server->addPlugin(
    new SchedulerPlugin(),
);

$server->addWorker(
    new WorkerProcess(
        name: 'Worker',
        count: 1,
        onStart: static function (WorkerProcess $worker): void {
            $worker->logger->notice("Hello from worker!");

            // You can also use the logger like this:
            $worker->logger->info('Hello from worker', ['pid' => \posix_getpid()]);
        }
    ),
    new PeriodicProcess(
        name: 'Periodic process',
        schedule: '*/1 * * * *',
        onStart: function (PeriodicProcess $worker): void {
            // process
            \App\Core\Support\Log::saveLog('server-log', ['Sceduler' => 'testing'], 'PHPStreamServer.Plugin.Scheduler', true);

            // You can also use the logger like this:
            $worker->logger->info('Scheduler is running', ['at' => date('d-m-Y H:i:s')]);
        },
    ),
);

exit($server->run());
