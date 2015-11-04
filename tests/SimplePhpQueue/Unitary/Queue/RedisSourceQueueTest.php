<?php
/**
 * User: Javier Bravo
 * Date: 10/05/15
 */

namespace SimplePhpQueue\Unitary\Queue;

use SimplePhpQueue\Queue\RedisQueue;

class RedisQueueTest extends \PHPUnit_Framework_TestCase {

    private $redisQueue;
    private $redisClientMock;

    protected function setUp() {
        $this->redisClientMock = $this->getMock('Predis\Client', array('brpoplpush', 'lrem', 'lpush', 'ping'));
        $this->redisQueue = new RedisQueue($this->redisClientMock, 'queue.test', 20);
    }

    public function testGetNext() {
        $returnExample = "{string: example}";
        $this->redisClientMock->expects($this->once())->method('brpoplpush')
            ->with('queue.test', 'queue.test:processing', 20)->willReturn($returnExample);
        $this->assertEquals($returnExample, $this->redisQueue->getNext());
    }

    public function testGetNextMaxWaitReached() {
        $returnExample = "{string: example}";
        $this->redisClientMock->expects($this->once())->method('brpoplpush')
            ->with('queue.test', 'queue.test:processing', 20)->willReturn(null);
        $this->assertTrue(false === $this->redisQueue->getNext());
    }

    public function testSuccess() {
        $task = '{data:sample}';
        $this->redisClientMock->expects($this->once())->method('lrem')->with('queue.test:processing', 1, $task);
        $this->redisQueue->successful($task);
    }

    public function testFailed() {
        $data = '{data:sample}';
        $this->redisClientMock->expects($this->once())->method('lpush')->with('queue.test:failed', $data);
        $this->redisClientMock->expects($this->once())->method('lrem')->with('queue.test:processing', 1, $data);
        $this->redisQueue->failed($data);
    }

    public function testError() {
        $data = '{data:sample}';
        $this->redisClientMock->expects($this->once())->method('lpush')->with('queue.test:error', $data);
        $this->redisClientMock->expects($this->once())->method('lrem')->with('queue.test:processing', 1, $data);
        $this->redisQueue->error($data);
    }

    public function testNothingToDo() {
        $this->redisClientMock->expects($this->once())->method('ping');
        $this->redisQueue->nothingToDo();
    }

    public function testStopped() {
        $data = '{data:sample}';
        $this->redisClientMock->expects($this->once())->method('lrem')->with('queue.test:processing', 1, $data);
        $this->redisQueue->stopped($data);
    }

}