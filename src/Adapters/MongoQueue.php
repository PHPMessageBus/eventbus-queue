<?php

namespace NilPortugues\MessageBus\EventBusQueue\Adapters;

use MongoDB\Client;
use MongoDB\Collection;
use NilPortugues\MessageBus\EventBus\Contracts\Event;
use NilPortugues\MessageBus\EventBusQueue\Contracts\Queue;
use NilPortugues\MessageBus\EventBusQueue\NullEvent;
use NilPortugues\MessageBus\Serializer\Contracts\Serializer;

class MongoQueue implements Queue
{
    /** @var Collection */
    protected $mongo;

    /** @var string */
    protected $queueName;

    /** @var Serializer */
    protected $serializer;

    /**
     * MongoQueue constructor.
     *
     * @param Serializer $serializer
     * @param Client     $client
     * @param string     $databaseName
     * @param string     $queueName
     */
    public function __construct(Serializer $serializer, Client $client, string $databaseName, string $queueName)
    {
        $this->serializer = $serializer;
        $this->queueName = $queueName;
        $this->mongo = $client->selectCollection($databaseName, $queueName);
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
        $this->mongo->insertOne([
            'data' => $this->serializer->serialize($event),
            'status' => 'pending',
            'created_at' => time(),
        ]);
    }

    /**
     * Returns an event from the Queue.
     *
     * @return Event
     */
    public function pop() : Event
    {
        $event = $this->mongo->findOneAndDelete(['status' => 'pending']);

        return ($event) ? $this->serializer->unserialize($event->data) : NullEvent::create();
    }

    /**
     * Returns true if queue has been fully processed or not, false otherwise.
     *
     * @return bool
     */
    public function hasElements(): bool
    {
        return 0 !== $this->mongo->count(['status' => 'pending']);
    }
}
