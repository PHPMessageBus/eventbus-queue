<?php
/**
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 * Date: 7/04/16
 * Time: 22:33.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NilPortugues\Tests\MessageBus\EventBusQueue\Adapters;

use NilPortugues\MessageBus\EventBusQueue\Adapters\AmqpQueue;
use NilPortugues\MessageBus\Serializer\NativeSerializer;
use NilPortugues\Tests\MessageBus\EventBusQueue\DummyEvent;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class AmqpQueueTest extends \PHPUnit_Framework_TestCase
{
    /** @var AMQPStreamConnection */
    protected $producerConnection;

    /** @var AMQPStreamConnection */
    protected $consumerConnection;

    /** @var NativeSerializer */
    protected $serializer;

    /** @var AmqpQueue */
    protected $consumer;

    /** @var AmqpQueue */
    protected $producer;

    public function setUp()
    {
        $this->consumerConnection = new AMQPStreamConnection('127.0.0.1', 5672, 'guest', 'guest');
        $this->producerConnection = new AMQPStreamConnection('127.0.0.1', 5672, 'guest', 'guest');
        $this->serializer = new NativeSerializer();

        $this->producer = new AmqpQueue($this->serializer, $this->producerConnection, 'testAdapterQueue');
        $this->consumer = new AmqpQueue($this->serializer, $this->consumerConnection, 'testAdapterQueue');
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
