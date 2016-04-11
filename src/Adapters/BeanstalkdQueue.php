<?php

namespace NilPortugues\MessageBus\EventBusQueue\Adapters;

use NilPortugues\MessageBus\EventBus\Contracts\Event;
use NilPortugues\MessageBus\EventBusQueue\Contracts\Queue;
use NilPortugues\MessageBus\EventBusQueue\NullEvent;
use NilPortugues\MessageBus\Serializer\Contracts\Serializer;
use Pheanstalk\PheanstalkInterface;

class BeanstalkdQueue implements Queue
{
    /** @var PheanstalkInterface */
    protected $pheanstalk;

    /** @var string */
    protected $queueName;

    /** @var Serializer */
    protected $serializer;

    /**
     * BeanstalkdProducerEventBusMiddleware constructor.
     *
     * @param Serializer          $serializer
     * @param PheanstalkInterface $pheanstalk
     * @param string              $queueName
     */
    public function __construct(Serializer $serializer, PheanstalkInterface $pheanstalk, string $queueName)
    {
        $this->serializer = $serializer;
        $this->pheanstalk = $pheanstalk;
        $this->queueName = $queueName;
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
        $this->pheanstalk->putInTube($this->queueName, $this->serializer->serialize($event));
    }

    /**
     * Returns an event from the Queue.
     *
     * @return Event
     */
    public function pop() : Event
    {
        /** @var \Pheanstalk\Job $event */
        $event = $this->pheanstalk->reserveFromTube($this->queueName);

        return ($event) ? $this->serializer->unserialize($event->getData()) : NullEvent::create();
    }

    /**
     * Returns true if queue has been fully processed or not, false otherwise.
     *
     * @return bool
     */
    public function hasElements(): bool
    {
        $stats = $this->pheanstalk->statsTube($this->queueName);

        return 0 !== $stats['current-jobs-ready'];
    }
}
