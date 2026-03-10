<?php


namespace App\Core\Queue;

use Closure;
use DateTimeInterface;
use InvalidPayloadException;

abstract class BpQueue
{


    /**
     * Create a payload string from the given job and data.
     *
     * @param  \Closure|string|object  $job
     * @param  string  $queue
     * @param  mixed  $data
     * @return string
     *
     * @throws InvalidPayloadException
     */
    protected function createPayload($job, $queue, $data = '')
    {
        // if ($job instanceof Closure) {
        //     $job = CallQueuedClosure::create($job);
        // }

        $payload = json_encode($value = $this->createPayloadArray($job, $queue, $data), \JSON_UNESCAPED_UNICODE);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidPayloadException(
                'Unable to JSON encode payload. Error ('.json_last_error().'): '.json_last_error_msg(), $value
            );
        }

        return $payload;
    }
}