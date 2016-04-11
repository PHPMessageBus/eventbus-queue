<?php

namespace NilPortugues\MessageBus\EventBusQueue\Adapters;

use NilPortugues\MessageBus\EventBus\Contracts\Event;
use NilPortugues\MessageBus\EventBusQueue\Contracts\Queue;
use NilPortugues\MessageBus\EventBusQueue\NullEvent;
use NilPortugues\MessageBus\Serializer\Contracts\Serializer;
use Predis\ClientInterface;

class PredisQueue implements Queue
{
    /** @var ClientInterface */
    protected $predis;

    /** @var string */
    protected $queueName;

    /** @var Serializer */
    protected $serializer;

    /**
     * AsynchronousEventBus constructor.
     *
     * @param Serializer      $serializer
     * @param ClientInterface $predisDriver
     * @param string          $queueName
     */
    public function __construct(Serializer $serializer, ClientInterface $predisDriver, string $queueName)
    {
        $this->predis = $predisDriver;
        $this->queueName = $queueName;
        $this->serializer = $serializer;
    }

    /**
     * Returns the name of the Queue.
     *
     * @return string
     */
    public function name() : string
    {
        return $this->queueName;
    }

    /**
     * Adds an event to the Queue.
     *
     * @param Event $event
     */
    public function push(Event $event)
    {
        $this->predis->rpush(
            $this->queueName,
            $this->serializer->serialize($event)
        );
    }

    /**
     * Returns an event from the Queue.
     *
     * @return Event
     */
    public function pop() : Event
    {
        $last = $this->predis->lPop($this->queueName);

        return ($last) ? $this->serializer->unserialize($last) : NullEvent::create();
    }

    /**
     * Returns true if queue has been fully processed or not, false otherwise.
     *
     * @return bool
     */
    public function hasElements(): bool
    {
        return 0 !== $this->predis->lLen($this->queueName);
    }
}
