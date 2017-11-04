<?php
/**
 * User: Javier Bravo
 * Date: 10/05/15
 */

namespace Simpleue\Unitary\Queue;

use Simpleue\Queue\SqsQueue;
use Aws\Result;

class SqsQueueTest extends \PHPUnit_Framework_TestCase {

    private $sqsQueue;
    private $sqsClientMock;

    protected function setUp() {
        $this->sqsClientMock = $this->getMockBuilder('Aws\Sqs\SqsClient')->disableOriginalConstructor()
            ->setMethods(array('receiveMessage', 'getQueueUrl', 'deleteMessage', 'sendMessage'))->getMock();
        $this->sqsClientMock->expects($this->any())->method('getQueueUrl')->willReturn(new Result(['QueueUrl' => 'queue-url-test']));
        $this->sqsQueue = new SqsQueue($this->sqsClientMock, 'queue-test', 20);
        $this->sqsQueue->setSourceQueueUrl('queue-url-source');
        $this->sqsQueue->setErrorQueueUrl('queue-url-error');
        $this->sqsQueue->setFailedQueueUrl('queue-url-failed');
    }

    public function testGetNext() {
        $messageContent = '{string: example}';
        $result = new Result(['Messages' => [$messageContent]]);
        $this->sqsClientMock->expects($this->once())->method('receiveMessage')->willReturn($result);
        $this->assertEquals($messageContent, $this->sqsQueue->getNext());
    }

    public function testGetNextMaxWaitReached() {
        $this->sqsClientMock->expects($this->once())->method('receiveMessage')->willReturn(new Result());
        $this->assertEquals(false, $this->sqsQueue->getNext());
    }

    public function testSuccess() {
        $data = '{data:sample}';
        $ReceipHandle = 'MyReceiptHandler';
        $job = ['Body' => $data, 'ReceiptHandle' => $ReceipHandle];
        $this->sqsClientMock->expects($this->once())->method('deleteMessage')->with(['QueueUrl' => 'queue-url-source', 'ReceiptHandle' => $ReceipHandle]);
        $this->sqsQueue->successful($job);
    }

    public function testFailed() {
        $data = '{data:sample}';
        $ReceipHandle = 'MyReceiptHandler';
        $job = ['Body' => $data, 'ReceiptHandle' => $ReceipHandle];
        $this->sqsClientMock->expects($this->once())->method('sendMessage')->with(['QueueUrl' => 'queue-url-failed', 'MessageBody' => $data]);
        $this->sqsClientMock->expects($this->once())->method('deleteMessage')->with(['QueueUrl' => 'queue-url-source', 'ReceiptHandle' => $ReceipHandle]);
        $this->sqsQueue->failed($job);
    }

    public function testError() {
        $data = '{data:sample}';
        $ReceipHandle = 'MyReceiptHandler';
        $job = ['Body' => $data, 'ReceiptHandle' => $ReceipHandle];
        $this->sqsClientMock->expects($this->once())->method('sendMessage')->with(['QueueUrl' => 'queue-url-error', 'MessageBody' => $data]);
        $this->sqsClientMock->expects($this->once())->method('deleteMessage')->with(['QueueUrl' => 'queue-url-source', 'ReceiptHandle' => $ReceipHandle]);
        $this->sqsQueue->error($job);
    }

    public function testStopped() {
        $data = '{data:sample}';
        $ReceipHandle = 'MyReceiptHandler';
        $job = ['Body' => $data, 'ReceiptHandle' => $ReceipHandle];
        $this->sqsClientMock->expects($this->once())->method('deleteMessage')->with(['QueueUrl' => 'queue-url-source', 'ReceiptHandle' => $ReceipHandle]);
        $this->sqsQueue->stopped($job);
    }

    public function testGetNextLocked()
    {
        $data = '{data:sample}';
        $ReceipHandle = 'MyReceiptHandler';
        $job = ['Body' => $data, 'ReceiptHandle' => $ReceipHandle];

        $redisLockerMock = $this->getMock(
            'Simpleue\Locker\RedisLocker',
            array('lock', 'getJobUniqId', 'getLockerInfo'),
            array(),
            '',
            false
        );
        $redisLockerMock->expects($this->at(0))->method('lock')->willReturn(false);
        $redisLockerMock->expects($this->at(1))->method('getJobUniqId')->willReturn('uniqid');
        $redisLockerMock->expects($this->at(2))->method('getLockerInfo')->willReturn('info');
        $this->sqsQueue->setLocker($redisLockerMock);

        $result = new Result(['Messages' => [$job]]);
        $this->sqsClientMock->expects($this->once())->method('receiveMessage')->willReturn($result);
        $this->sqsClientMock->expects($this->once())->method('sendMessage')->with(['QueueUrl' => 'queue-url-error', 'MessageBody' => $data]);
        $this->sqsClientMock->expects($this->once())->method('deleteMessage')->with(['QueueUrl' => 'queue-url-source', 'ReceiptHandle' => $ReceipHandle]);
        $this->setExpectedException(
            'RuntimeException',
            'Sqs msg lock cannot acquired!'
            .' LockId: uniqid'
            .' LockerInfo: info'
        );
        $this->sqsQueue->getNext($job);
    }

}