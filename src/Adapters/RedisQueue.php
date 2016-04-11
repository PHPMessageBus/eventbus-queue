<?php

namespace NilPortugues\MessageBus\EventBusQueue\Adapters;

use NilPortugues\MessageBus\EventBus\Contracts\Event;
use NilPortugues\MessageBus\EventBusQueue\Contracts\Queue;
use NilPortugues\MessageBus\EventBusQueue\NullEvent;
use NilPortugues\MessageBus\Serializer\Contracts\Serializer;
use Redis;

class RedisQueue implements Queue
{
    /** @var Redis */
    protected $redis;

    /** @var string */
    protected $queueName;

    /** @var Serializer */
    protected $serializer;

    /**
     * AsynchronousRedisEventBus constructor.
     *
     * @param Serializer $serializer
     * @param Redis      $redis
     * @param string     $queueName
     */
    public function __construct(Serializer $serializer, Redis $redis, string $queueName)
    {
        $this->redis = $redis;

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
        $this->redis->rPush($this->queueName, $this->serializer->serialize($event));
    }

    /**
     * Returns an event from the Queue.
     *
     * @return Event
     */
    public function pop() : Event
    {
        $last = $this->redis->lPop($this->queueName);

        return ($last) ? $this->serializer->unserialize($last) : NullEvent::create();
    }

    /**
     * Returns true if queue has been fully processed or not, false otherwise.
     *
     * @return bool
     */
    public function hasElements(): bool
    {
        return 0 !== $this->redis->lLen($this->queueName);
    }
}
