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

use NilPortugues\MessageBus\EventBusQueue\Adapters\PredisQueue;
use NilPortugues\MessageBus\Serializer\NativeSerializer;
use NilPortugues\Tests\MessageBus\EventBusQueue\DummyEvent;
use Predis\Client;

class PredisQueueTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Predis\Client */
    protected $producerConnection;

    /** @var \Predis\Client */
    protected $consumerConnection;

    /** @var NativeSerializer */
    protected $serializer;

    /** @var PredisQueue */
    protected $consumer;

    /** @var PredisQueue */
    protected $producer;

    /** @var Client */
    protected $predis;

    public function setUp()
    {
        $this->predis = new Client();

        $this->consumerConnection = $this->predis;
        $this->producerConnection = $this->predis;
        $this->serializer = new NativeSerializer();

        $this->producer = new PredisQueue($this->serializer, $this->producerConnection, 'testAdapterQueue');
        $this->consumer = new PredisQueue($this->serializer, $this->consumerConnection, 'testAdapterQueue');
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
        $this->predis->del($this->consumer->name());
    }
}
