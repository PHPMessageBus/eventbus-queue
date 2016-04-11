<?php

namespace NilPortugues\MessageBus\EventBusQueue\Adapters;

use InvalidArgumentException;
use NilPortugues\MessageBus\EventBus\Contracts\Event;
use NilPortugues\MessageBus\EventBusQueue\Contracts\Queue;
use NilPortugues\MessageBus\EventBusQueue\NullEvent;
use NilPortugues\MessageBus\Serializer\Contracts\Serializer;
use NilPortugues\Uuid\Uuid;

class FileSystemQueue implements Queue
{
    /** @var string */
    protected $baseDirectory;

    /** @var int */
    protected $permissions = 0740;

    /** @var string */
    protected $queueName;

    /** @var Serializer */
    protected $serializer;

    /**
     * FileSystemEventMiddleware constructor.
     *
     * @param Serializer $serializer
     * @param string     $baseDirectory
     * @param string     $queueName
     */
    public function __construct(Serializer $serializer, string $baseDirectory, string $queueName)
    {
        $this->guard($baseDirectory);

        $this->baseDirectory = $baseDirectory;
        $this->serializer = $serializer;
        $this->queueName = $queueName;
    }

    /**
     * @param string $directory
     *
     * @throws InvalidArgumentException
     */
    protected function guard(string $directory)
    {
        if (false === \is_dir($directory)) {
            throw new InvalidArgumentException(\sprintf('The provided path %s is not a valid directory', $directory));
        }

        if (false === \is_writable($directory)) {
            throw new InvalidArgumentException(\sprintf('The provided directory %s is not writable', $directory));
        }
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
        file_put_contents($this->filePath(), $this->serializer->serialize($event).PHP_EOL);
    }

    /**
     * Returns an event from the Queue.
     *
     * @return Event
     */
    public function pop() : Event
    {
        $iterator = $this->directoryIterator();

        if ($this->directoryIterator()->count()) {
            $iteratorArray = iterator_to_array($iterator, true);
            $fileName = key($iteratorArray);

            $event = file_get_contents($this->baseDirectory.DIRECTORY_SEPARATOR.$fileName);
            $event = $this->serializer->unserialize($event);
            unlink($this->baseDirectory.DIRECTORY_SEPARATOR.$fileName);

            return $event;
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
        $iterator = $this->directoryIterator();

        return false === empty($iterator->count());
    }

    /**
     * @return string
     */
    protected function filePath() : string
    {
        return sprintf('%s/%s.%s.job.php', $this->baseDirectory, $this->queueName, Uuid::create());
    }

    /**
     * @return \GlobIterator
     */
    protected function directoryIterator()
    {
        $iterator = new \GlobIterator(
            $this->baseDirectory.DIRECTORY_SEPARATOR.'*.job.php',
            \FilesystemIterator::KEY_AS_FILENAME
        );

        return $iterator;
    }
}
