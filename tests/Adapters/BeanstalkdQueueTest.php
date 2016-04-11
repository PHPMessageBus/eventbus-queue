<?php
/**
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 * Date: 7/04/16
 * Time: 22:57.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NilPortugues\Tests\MessageBus\EventBusQueue\Adapters;

use NilPortugues\MessageBus\EventBusQueue\Adapters\BeanstalkdQueue;
use NilPortugues\MessageBus\Serializer\NativeSerializer;
use NilPortugues\Tests\MessageBus\EventBus\DummyEvent;
use Pheanstalk\Pheanstalk;

class BeanstalkdQueueTest extends \PHPUnit_Framework_TestCase
{
    /** @var Pheanstalk */
    protected $producerConnection;

    /** @var Pheanstalk */
    protected $consumerConnection;

    /** @var NativeSerializer */
    protected $serializer;

    /** @var BeanstalkdQueue */
    protected $consumer;

    /** @var BeanstalkdQueue */
    protected $producer;

    public function setUp()
    {
        $this->consumerConnection = new Pheanstalk('127.0.0.1');
        $this->producerConnection = new Pheanstalk('127.0.0.1');
        $this->serializer = new NativeSerializer();

        $this->producer = new BeanstalkdQueue($this->serializer, $this->producerConnection, 'testAdapterQueue');
        $this->consumer = new BeanstalkdQueue($this->serializer, $this->consumerConnection, 'testAdapterQueue');
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
