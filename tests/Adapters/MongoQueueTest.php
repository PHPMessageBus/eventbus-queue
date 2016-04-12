<?php
/**
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 * Date: 8/04/16
 * Time: 0:34.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NilPortugues\Tests\MessageBus\EventBusQueue\Adapters;

use MongoDB\Client;
use NilPortugues\MessageBus\EventBusQueue\Adapters\MongoQueue;
use NilPortugues\MessageBus\Serializer\NativeSerializer;
use NilPortugues\Tests\MessageBus\EventBusQueue\DummyEvent;

class MongoQueueTest extends \PHPUnit_Framework_TestCase
{
    /** @var Client */
    protected $producerConnection;

    /** @var Client */
    protected $consumerConnection;

    /** @var NativeSerializer */
    protected $serializer;

    /** @var MongoQueue */
    protected $consumer;

    /** @var MongoQueue */
    protected $producer;

    public function setUp()
    {
        $this->consumerConnection = new Client();
        $this->producerConnection = new Client();
        $this->serializer = new NativeSerializer();

        $this->producer = new MongoQueue($this->serializer, $this->producerConnection, 'test', 'testAdapterQueue');
        $this->consumer = new MongoQueue($this->serializer, $this->consumerConnection, 'test', 'testAdapterQueue');
    }

    public function testAdapterQueue()
    {
        $event = new DummyEvent();
        $this->producer->push($event);

        $this->assertTrue($this->producer->hasElements());
        $this->assertEquals($event, $this->consumer->pop());
    }

    public function testName()
    {
        $this->assertEquals('testAdapterQueue', $this->consumer->name());
    }
}
