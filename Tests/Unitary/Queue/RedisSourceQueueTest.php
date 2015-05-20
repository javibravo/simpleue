<?php
/**
 * Created by PhpStorm.
 * User: jbravo
 * Date: 10/05/15
 * Time: 17:01
 */

namespace Tests\Unitary\Queue;

require_once dirname(__FILE__).'/../../autoload.php';

use SimplePhpQueue\Queue\RedisSourceQueue;

class RedisSourceQueueTest extends \PHPUnit_Framework_TestCase {

    private $redisSourceQueue;
    private $redisClientMock;

    protected function setUp() {
        $this->redisClientMock = $this->getMock('Predis\Client', array('brpoplpush', 'lrem', 'lpush', 'ping'));
        $this->redisSourceQueue = new RedisSourceQueue($this->redisClientMock, 'queue.test', 20);
    }

    public function testGetNext() {
        $this->redisClientMock->expects($this->once())->method('brpoplpush')->with('queue.test', 'queue.test:processing', 20);
        $this->redisSourceQueue->getNext();
    }

    public function testSuccess() {
        $task = '{data:sample}';
        $this->redisClientMock->expects($this->once())->method('lrem')->with('queue.test:processing', 1, $task);
        $this->redisSourceQueue->successful($task);
    }

    public function testFailed() {
        $data = '{data:sample}';
        $this->redisClientMock->expects($this->once())->method('lpush')->with('queue.test:failed', $data);
        $this->redisClientMock->expects($this->once())->method('lrem')->with('queue.test:processing', 1, $data);
        $this->redisSourceQueue->failed($data);
    }

    public function testError() {
        $data = '{data:sample}';
        $this->redisClientMock->expects($this->once())->method('lpush')->with('queue.test:error', $data);
        $this->redisClientMock->expects($this->once())->method('lrem')->with('queue.test:processing', 1, $data);
        $this->redisSourceQueue->error($data);
    }

    public function testNothingToDo() {
        $this->redisClientMock->expects($this->once())->method('ping');
        $this->redisSourceQueue->nothingToDo();
    }

}