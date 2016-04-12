<?php

namespace NilPortugues\Tests\MessageBus\EventBusQueue;

use NilPortugues\MessageBus\EventBus\EventBus;
use NilPortugues\MessageBus\EventBus\EventBusMiddleware;
use NilPortugues\MessageBus\EventBus\Resolver\SimpleArrayResolver;
use NilPortugues\MessageBus\EventBus\Translator\EventFullyQualifiedClassNameStrategy;
use NilPortugues\MessageBus\EventBusQueue\Adapters\FileSystemQueue;
use NilPortugues\MessageBus\EventBusQueue\EventBusWorker;
use NilPortugues\MessageBus\EventBusQueue\ProducerEventBusMiddleware;
use NilPortugues\MessageBus\Serializer\NativeSerializer;

class EventBusWorkerTest extends \PHPUnit_Framework_TestCase
{
    /** @var string */
    protected $dirPath;

    /** @var array */
    protected $handlers;

    /** @var EventFullyQualifiedClassNameStrategy */
    protected $translator;

    /** @var SimpleArrayResolver */
    protected $resolver;

    /** @var NativeSerializer */
    protected $serializer;

    /** @var EventBus */
    protected $consumerEventBus;

    /** @var EventBus */
    protected $producerEventBus;

    /** @var FileSystemQueue */
    protected $consumerQueue;

    /** @var FileSystemQueue */
    protected $errorQueue;
    /** @var string */
    protected $queueName = 'testQueueABC';

    public function setUp()
    {
        $this->handlers = [
            DummyEventHandler::class => function () {
                return new DummyEventHandler();
            },
        ];

        $this->translator = new EventFullyQualifiedClassNameStrategy([
            DummyEventHandler::class,
        ]);

        $this->resolver = new SimpleArrayResolver($this->handlers);
        $this->serializer = new NativeSerializer();

        $this->setUpQueue();

        $this->producerEventBus = new EventBus([
            new ProducerEventBusMiddleware($this->consumerQueue),
            new EventBusMiddleware($this->translator, $this->resolver),
        ]);

        $this->consumerEventBus = new EventBus([
            new EventBusMiddleware($this->translator, $this->resolver),
        ]);
    }

    protected function setUpQueue()
    {
        mkdir(__DIR__.'/jobs', 0777, true);
        mkdir(__DIR__.'/errored-jobs', 0777, true);
        $this->consumerQueue = new FileSystemQueue($this->serializer, __DIR__.'/jobs', $this->queueName);
        $this->errorQueue = new FileSystemQueue($this->serializer, __DIR__.'/errored-jobs', $this->queueName);
    }

    public function testItCanConsume()
    {
        for ($i = 1; $i <= 10; ++$i) {
            $this->producerEventBus->__invoke(new DummyEvent());
        }
        $consumer = new EventBusWorker();
        $consumer->consume($this->consumerQueue, $this->errorQueue, $this->consumerEventBus);
    }

    public function testItCanCatchExceptionWhileConsume()
    {
        for ($i = 1; $i <= 10; ++$i) {
            $this->producerEventBus->__invoke(new DummyEvent());
        }

        $this->consumerEventBus = new EventBus([
            new ThrowsExceptionEventBusMiddleware(),
            new EventBusMiddleware($this->translator, $this->resolver),
        ]);

        $consumer = new EventBusWorker();
        $consumer->consume($this->consumerQueue, $this->errorQueue, $this->consumerEventBus);
    }

    public function tearDown()
    {
        $this->emptyDir(__DIR__.'/errored-jobs');
        $this->emptyDir(__DIR__.'/jobs');
    }

    protected function emptyDir($path)
    {
        if (file_exists($path)) {
            foreach (\glob($path.'/*') as $file) {
                unlink($file);
            }
        }
        rmdir($path);
    }
}
