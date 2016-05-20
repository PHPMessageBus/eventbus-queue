<?php

namespace NilPortugues\MessageBus\EventBusQueue\Adapters;

use NilPortugues\MessageBus\EventBus\Contracts\Event;
use NilPortugues\MessageBus\EventBusQueue\Contracts\Queue;
use NilPortugues\MessageBus\EventBusQueue\NullEvent;
use NilPortugues\MessageBus\Serializer\Contracts\Serializer;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class AmqpQueue implements Queue
{
    /** @var \PhpAmqpLib\Channel\AMQPChannel */
    protected $amqpChannel;

    /** @var string */
    protected $queueName;

    /** @var Serializer */
    protected $serializer;

    /** @var bool */
    protected $isDeclared = false;

    /**
     * AsyncAmqpEventBusMiddleware constructor.
     *
     * @param Serializer           $serializer
     * @param AMQPStreamConnection $streamConnection
     * @param string               $queueName
     */
    public function __construct(Serializer $serializer, AMQPStreamConnection $streamConnection, string $queueName)
    {
        $this->serializer = $serializer;
        $this->amqpChannel = $streamConnection->channel();
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
        $this->declareQueue();
        $this->amqpChannel->basic_publish(
            new AMQPMessage($this->serializer->serialize($event), ['delivery_mode' => 2]),
            '',
            $this->queueName,
            true
        );
    }

    /**
     * Returns an event from the Queue.
     *
     * @return Event
     */
    public function pop() : Event
    {
        $this->declareQueue();
        $message = $this->amqpChannel->basic_get($this->queueName);

        if (!empty($message)) {
            $this->amqpChannel->basic_ack($message->delivery_info['delivery_tag']);
        }

        return ($message) ? $this->serializer->unserialize($message->body) : NullEvent::create();
    }

    /**
     * Returns true if queue has been fully processed or not, false otherwise.
     *
     * @return bool
     */
    public function hasElements(): bool
    {
        $hasElements = false;

        $message = $this->amqpChannel->basic_get($this->queueName);
        if ($message) {
            $hasElements = true;
        }

        return $hasElements;
    }

    /**
     *
     */
    protected function declareQueue()
    {
        if (false === $this->isDeclared) {
            $this->amqpChannel->queue_declare($this->queueName, false, false, false, false);
            $this->isDeclared = true;
        }
    }
}
