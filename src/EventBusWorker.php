<?php

namespace NilPortugues\MessageBus\EventBusQueue;

use Exception;
use NilPortugues\MessageBus\EventBus\Contracts\EventBusMiddleware;
use NilPortugues\MessageBus\EventBusQueue\Contracts\Queue;
use NilPortugues\MessageBus\EventBusQueue\Contracts\Worker;

class EventBusWorker implements Worker
{
    /**
     * @param Queue              $consumerQueue
     * @param Queue              $errorQueue
     * @param EventBusMiddleware $worker
     */
    public function consume(Queue $consumerQueue, Queue $errorQueue, EventBusMiddleware $worker)
    {
        while ($event = $consumerQueue->pop()) {
            try {
                if (false === $event instanceof NullEvent) {
                    $worker($event);
                } else {
                    break;
                }
            } catch (Exception $e) {
                if (!empty($event) && (false === $event instanceof NullEvent)) {
                    $errorQueue->push($event);
                }
            }
        }
    }
}
