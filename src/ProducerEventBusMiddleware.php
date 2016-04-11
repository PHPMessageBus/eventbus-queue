<?php

namespace NilPortugues\MessageBus\EventBusQueue;

use NilPortugues\MessageBus\EventBus\Contracts\Event;
use NilPortugues\MessageBus\EventBus\Contracts\EventBusMiddleware as EventBusMiddlewareInterface;
use NilPortugues\MessageBus\EventBusQueue\Contracts\Queue;

class ProducerEventBusMiddleware implements EventBusMiddlewareInterface
{
    /** @var Queue */
    protected $queue;

    /**
     * ConsumerEventBusMiddleware constructor.
     *
     * @param Queue $queue
     */
    public function __construct(Queue $queue)
    {
        $this->queue = $queue;
    }

    /**
     * @param Event         $event
     * @param callable|null $next
     */
    public function __invoke(Event $event, callable $next = null)
    {
        $this->queue->push($event);

        if ($next) {
            $next($event);
        }
    }
}
