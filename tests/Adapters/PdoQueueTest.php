<?php
/**
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 * Date: 8/04/16
 * Time: 0:36.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NilPortugues\Tests\MessageBus\EventBusQueue\Adapters;

use NilPortugues\MessageBus\EventBusQueue\Adapters\PdoQueue;
use NilPortugues\MessageBus\EventBusQueue\NullEvent;
use NilPortugues\MessageBus\Serializer\NativeSerializer;
use NilPortugues\Tests\MessageBus\EventBus\DummyEvent;
use PDO;

class PdoQueueTest extends \PHPUnit_Framework_TestCase
{
    /** @var PDO */
    protected $producerConnection;

    /** @var PDO */
    protected $consumerConnection;

    /** @var NativeSerializer */
    protected $serializer;

    /** @var PdoQueue */
    protected $consumer;

    /** @var PdoQueue */
    protected $producer;

    public function setUp()
    {
        $this->producerConnection = $this->consumerConnection = new PDO('sqlite::memory:');
        $this->producerConnection->exec('CREATE TABLE testAdapterQueue (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  event_data TEXT NOT NULL,
  event_status CHAR(50),
  created_at INTEGER NOT NULL
);'
        );

        $this->serializer = new NativeSerializer();

        $this->producer = new PdoQueue($this->serializer, $this->producerConnection, 'testAdapterQueue');
        $this->consumer = new PdoQueue($this->serializer, $this->consumerConnection, 'testAdapterQueue');
    }

    public function testAdapterQueue()
    {
        $event = new DummyEvent();
        $this->producer->push($event);

        $this->assertTrue($this->producer->hasElements());
        $this->assertEquals($event, $this->consumer->pop());
    }

    public function testAdapterQueueReturnNullEvent()
    {
        $queue = new PdoQueue($this->serializer, $this->producerConnection, 'testAdapterQueue');
        $this->assertInstanceOf(NullEvent::class, $queue->pop());
    }

    public function testName()
    {
        $this->assertEquals('testAdapterQueue', $this->consumer->name());
    }
}
