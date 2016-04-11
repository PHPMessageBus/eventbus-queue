<?php

namespace NilPortugues\MessageBus\EventBusQueue\Contracts;

use NilPortugues\MessageBus\EventBus\Contracts\Event;

interface Queue
{
    /**
     * Returns the name of the Queue.
     *
     * @return string
     */
    public function name() : string;

    /**
     * Adds an event to the Queue.
     *
     * @param Event $event
     */
    public function push(Event $event);

    /**
     * Returns an event from the Queue.
     *
     * @return Event
     */
    public function pop() : Event;

    /**
     * Returns true if queue has been fully processed or not, false otherwise.
     *
     * @return bool
     */
    public function hasElements(): bool;
}
