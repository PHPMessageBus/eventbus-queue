<?php
/**
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 * Date: 7/04/16
 * Time: 23:03.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NilPortugues\Tests\MessageBus\EventBusQueue\Adapters;

use InvalidArgumentException;
use NilPortugues\MessageBus\EventBusQueue\Adapters\FileSystemQueue;
use NilPortugues\MessageBus\EventBusQueue\NullEvent;
use NilPortugues\MessageBus\Serializer\NativeSerializer;
use NilPortugues\Tests\MessageBus\EventBusQueue\DummyEvent;

class FileSystemQueueTest extends \PHPUnit_Framework_TestCase
{
    /** @var NativeSerializer */
    protected $serializer;

    /** @var FileSystemQueue */
    protected $consumer;

    /** @var FileSystemQueue */
    protected $producer;

    /** @var string */
    protected $dirPath;

    public function setUp()
    {
        $this->serializer = new NativeSerializer();

        $path = __DIR__.'/../jobs/';
        if (false === file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $this->dirPath = realpath($path);

        $this->producer = new FileSystemQueue($this->serializer, $this->dirPath, 'testAdapterQueue');
        $this->consumer = new FileSystemQueue($this->serializer, $this->dirPath, 'testAdapterQueue');
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
        $queue = new FileSystemQueue($this->serializer, '.', 'testAdapterQueue');
        $this->assertInstanceOf(NullEvent::class, $queue->pop());
    }

    public function testName()
    {
        $this->assertEquals('testAdapterQueue', $this->consumer->name());
    }

    public function testItWillThrowExceptionIfDirectoryDoesNotExist()
    {
        $this->expectException(InvalidArgumentException::class);

        new FileSystemQueue($this->serializer, '/nope', 'testAdapterQueue');
    }

    public function testItThrowsExceptionWhenDirDoesNotExist()
    {
        $this->expectException(InvalidArgumentException::class);

        new FileSystemQueue($this->serializer, '/nope', 'testAdapterQueue');
    }

    public function testItThrowsExceptionWhenDirIsNotWritable()
    {
        $this->expectException(InvalidArgumentException::class);

        new FileSystemQueue($this->serializer, '/', 'testAdapterQueue');
    }

    public function tearDown()
    {
        if (file_exists($this->dirPath)) {
            foreach (\glob("{$this->dirPath}/*") as $file) {
                unlink($file);
            }
        }
        rmdir($this->dirPath);
    }
}
