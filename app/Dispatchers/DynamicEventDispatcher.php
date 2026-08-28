<?php

namespace App\Dispatchers;

use App\Core\Support\App;
use Symfony\Component\EventDispatcher\EventDispatcher as SymfonyEventDispatcher;
use OpenSwoole\Server;

class DynamicEventDispatcher
{
    protected ?Server $swooleServer;

    public function __construct(?Server $swooleServer = null)
    {
        $this->swooleServer = $swooleServer;
    }

    /**
     * Dispatch events asynchronously using the Swoole Task Queue.
     */
    public function dispatchAsync(object $event, string $method = 'handle'): void
    {
        $eventClass = get_class($event);
        $eventName = defined("{$eventClass}::NAME") ? $event::NAME : $eventClass;

        $events = App::get('events');

        if (empty($events) || (!is_array($events) && !($events instanceof \ArrayAccess))) {
            return;
        }

        // Search for listeners based on Event Name (for example 'order.placed') or FQCN Class
        $listeners = $events[$eventName] ?? $events[$eventClass] ?? [];

        if (empty($listeners)) {
            // Log if the listener is not found for debugging
            echo "[DISPATCHER WARNING] No listeners found for event: {$eventName} / {$eventClass}\n";
            return;
        }

        foreach ($listeners as $listenerClass) {
            $taskData = [
                'listener' => $listenerClass,
                'method'   => $method,
                'event'    => $event
            ];

            // Safe check: If the Swoole Server is active & NOT inside the Task Worker
            if ($this->swooleServer && !$this->swooleServer->taskworker) {
                $taskId = $this->swooleServer->task($taskData);

                if ($taskId === false) {
                    echo "[DISPATCHER ERROR] Failed pushing task to OpenSwoole Task Queue\n";
                }
            } else {
                // Fallback Sync if running in regular CLI/Unit Test
                self::executeListener($listenerClass, $method, $event);
            }
        }
    }

    /**
     * Helper to execute listeners using Symfony EventDispatcher
     */
    public static function executeListener(string $listenerClass, string $method, object $event): void
    {
        if (!class_exists($listenerClass)) {
            echo "[TASK ERROR] Listener class [{$listenerClass}] does not exist.\n";
            return;
        }

        $eventClass = get_class($event);
        $eventName = defined("{$eventClass}::NAME") ? $event::NAME : $eventClass;

        $listenerInstance = new $listenerClass();

        // 1. Check whether the requested method ($method) exists
        $targetMethod = null;
        if (method_exists($listenerInstance, $method)) {
            $targetMethod = $method;
        } elseif (method_exists($listenerInstance, 'handle')) {
            // Fallback 1: Check the handle() method
            $targetMethod = 'handle';
        } elseif (method_exists($listenerInstance, 'on' . space_to_pascal($eventName))) {
            // Fallback 2: Check the specific method of the event name (for example onOrderPlaced)
            $targetMethod = 'on' . space_to_pascal($eventName);
        }

        if (!$targetMethod) {
            echo "[TASK ERROR] Method [{$method}] not found in listener [{$listenerClass}].\n";
            return;
        }

        // 2. Registration and execution via Symfony EventDispatcher
        $dispatcher = new SymfonyEventDispatcher();
        $dispatcher->addListener($eventName, [$listenerInstance, $targetMethod]);

        if ($eventName !== $eventClass) {
            $dispatcher->addListener($eventClass, [$listenerInstance, $targetMethod]);
        }

        $dispatcher->dispatch($event, $eventName);
    }
}
