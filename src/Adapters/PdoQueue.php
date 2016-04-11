<?php

namespace NilPortugues\MessageBus\EventBusQueue\Adapters;

use Doctrine\DBAL\DriverManager;
use NilPortugues\MessageBus\EventBus\Contracts\Event;
use NilPortugues\MessageBus\EventBusQueue\Contracts\Queue;
use NilPortugues\MessageBus\EventBusQueue\NullEvent;
use NilPortugues\MessageBus\Serializer\Contracts\Serializer;
use PDO;

class PdoQueue implements Queue
{
    /** @var Serializer */
    protected $serializer;

    /** @var \Doctrine\DBAL\Connection */
    protected $connection;

    /** @var string */
    protected $queueName;

    /**
     * ProducerPdoEventBusMiddleware constructor.
     *
     * @param Serializer $serializer
     * @param PDO        $pdo
     * @param string     $queueName
     */
    public function __construct(Serializer $serializer, PDO $pdo, string $queueName)
    {
        $this->serializer = $serializer;
        $this->connection = DriverManager::getConnection(['pdo' => $pdo]);
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
        $this->connection
            ->createQueryBuilder()
            ->insert($this->queueName)
            ->setValue('event_data', '?')
            ->setValue('event_status', '?')
            ->setValue('created_at', '?')
            ->setParameter(0, $this->serializer->serialize($event))
            ->setParameter(1, 'pending')
            ->setParameter(2, time())
            ->execute();
    }

    /**
     * Returns an event from the Queue.
     *
     * @return Event
     */
    public function pop() : Event
    {
        $pop = $this->connection
            ->createQueryBuilder()
            ->from($this->queueName)
            ->select('*')
            ->execute()
            ->fetch();

        if ($pop) {
            $this->connection
                ->createQueryBuilder()
                ->update($this->queueName)
                ->set('event_status', '?')
                ->where('id = ?')
                ->setParameter(0, 'done')
                ->setParameter(1, $pop['id'])
                ->execute();

            return $this->serializer->unserialize($pop['event_data']);
        }

        return NullEvent::create();
    }

    /**
     * Returns true if queue has been fully processed or not, false otherwise.
     *
     * @return bool
     */
    public function hasElements(): bool
    {
        $total = $this->connection
            ->executeQuery(sprintf('SELECT COUNT(*) AS totalCount FROM %s WHERE event_status = \'pending\';', $this->queueName))
            ->fetch();

        return 0 !== (int) $total['totalCount'];
    }
}
