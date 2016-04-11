<?php

namespace NilPortugues\Tests\MessageBus\EventBusQueue;

use NilPortugues\MessageBus\EventBus\Contracts\Event;
use NilPortugues\MessageBus\EventBus\Contracts\EventBusMiddleware;

class ThrowsExceptionEventBusMiddleware implements EventBusMiddleware
{
    /**
     * @param Event         $event
     * @param callable|null $next
     *
     * @throws \Exception
     */
    public function __invoke(Event $event, callable $next = null)
    {
        throw new \Exception('I failed :(');
    }
}
