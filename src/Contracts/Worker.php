<?php

namespace NilPortugues\MessageBus\EventBusQueue\Contracts;

use NilPortugues\MessageBus\EventBus\Contracts\EventBusMiddleware;

interface Worker
{
    /**
     * Consumes a queue.
     *
     * @param Queue              $consumerQueue
     * @param Queue              $errorQueue
     * @param EventBusMiddleware $worker
     *
     * @return mixed
     */
    public function consume(Queue $consumerQueue, Queue $errorQueue, EventBusMiddleware $worker);
}
