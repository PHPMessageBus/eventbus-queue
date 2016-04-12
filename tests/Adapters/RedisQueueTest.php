<?php
/**
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 * Date: 8/04/16
 * Time: 1:00.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NilPortugues\Tests\MessageBus\EventBusQueue\Adapters;

use NilPortugues\MessageBus\EventBusQueue\Adapters\RedisQueue;
use NilPortugues\MessageBus\Serializer\NativeSerializer;
use NilPortugues\Tests\MessageBus\EventBusQueue\DummyEvent;
use Redis;

class RedisQueueTest extends \PHPUnit_Framework_TestCase
{
    /** @var Redis */
    protected $producerConnection;

    /** @var Redis */
    protected $consumerConnection;

    /** @var NativeSerializer */
    protected $serializer;

    /** @var RedisQueue */
    protected $consumer;

    /** @var RedisQueue */
    protected $producer;

    /** @var Redis */
    protected $redis;

    public function setUp()
    {
        $this->redis = new Redis();
        $this->redis->connect('127.0.0.1');

        $this->consumerConnection = $this->redis;
        $this->producerConnection = $this->redis;
        $this->serializer = new NativeSerializer();

        $this->producer = new RedisQueue($this->serializer, $this->producerConnection, 'testAdapterQueue');
        $this->consumer = new RedisQueue($this->serializer, $this->consumerConnection, 'testAdapterQueue');
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

    public function tearDown()
    {
        $this->redis->del($this->consumer->name());
    }
}
