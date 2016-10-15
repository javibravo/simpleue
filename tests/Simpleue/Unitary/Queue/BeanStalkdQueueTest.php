<?php

namespace Simpleue\Unitary\Queue;

use Pheanstalk\Job;
use Simpleue\Queue\BeanStalkdQueue;

/**
 * Class BeanStalkdQueueTest
 * @author Adeyemi Olaoye <yemexx1@gmail.com>
 * @package Simpleue\Unitary\Queue
 */
class BeanStalkdQueueTest extends \PHPUnit_Framework_TestCase
{
    private $beanStalkdQueue;
    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    private $beanStalkdClientMock;
    private $testQueueName;

    protected function setUp()
    {
        $this->beanStalkdClientMock = $this->getMockBuilder('Pheanstalk\Pheanstalk')->disableOriginalConstructor()
            ->setMethods(['put', 'delete', 'useTube', 'reserve', 'putInTube'])->getMock();
        $this->testQueueName = 'queue-test';
        $this->beanStalkdQueue = new BeanStalkdQueue($this->beanStalkdClientMock, $this->testQueueName);
    }

    public function testGetNext()
    {
        $returnExample = new Job(1, '{string: example}');
        $this->beanStalkdClientMock->expects($this->once())->method('reserve')->willReturn($returnExample);
        $this->assertEquals($returnExample->getData(), $this->beanStalkdQueue->getNext()->getData());
    }

    public function testGetNextMaxWaitReached()
    {
        $this->beanStalkdClientMock->expects($this->once())->method('reserve')->willReturn(false);
        $this->assertTrue(false === $this->beanStalkdQueue->getNext());
    }

    public function testSuccess()
    {
        $job = new Job(1, '{data:sample}');
        $this->beanStalkdClientMock->expects($this->once())->method('delete')->with($job);
        $this->beanStalkdQueue->successful($job);
    }

    public function testFailed()
    {
        $job = new Job(1, '{data:sample}');
        $this->beanStalkdClientMock->expects($this->once())->method('delete')->with($job);
        $this->beanStalkdClientMock->expects($this->once())->method('putInTube')
            ->with($this->testQueueName . '-failed', $job->getData());
        $this->beanStalkdQueue->failed($job);
    }

    public function testError()
    {
        $job = new Job(1, '{data:sample}');
        $this->beanStalkdClientMock->expects($this->once())->method('delete')->with($job);
        $this->beanStalkdClientMock->expects($this->once())->method('putInTube')
            ->with($this->testQueueName . '-error', $job->getData());
        $this->beanStalkdQueue->error($job);
    }


    public function testStopped()
    {
        $job = new Job(1, '{data:sample}');
        $this->beanStalkdClientMock->expects($this->once())->method('delete')->with($job);
        $this->beanStalkdQueue->stopped($job);
    }
}
