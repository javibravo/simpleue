<?php
/**
 * User: Javier Bravo
 * Date: 10/05/15
 */

namespace SimplePhpQueue\Unitary\Worker;

use SimplePhpQueue\Mocks\QueueWorkerSpy;
use SimplePhpQueue\Mocks\QueueSpy;
use SimplePhpQueue\Mocks\TaskSpy;
use SimplePhpQueue\Mocks\LoggerSpy;

class QueueWorkerTest extends \PHPUnit_Framework_TestCase {

    private $queueWorkerSpy;
    private $sourceQueueMock;
    private $taskHandlerMock;

    protected function setUp() {
        date_default_timezone_set('Europe/London');

        $this->sourceQueueMock = new QueueSpy();
        $this->taskHandlerMock = new TaskSpy();
        $this->queueWorkerSpy = new QueueWorkerSpy($this->sourceQueueMock, $this->taskHandlerMock);
    }

    public function testRunMaxIterations() {
        $this->queueWorkerSpy->setMaxIterations(3);
        $this->queueWorkerSpy->start();
        $this->assertEquals(3, $this->queueWorkerSpy->getIterations());

        $this->queueWorkerSpy->setMaxIterations(10);
        $this->queueWorkerSpy->start();
        $this->assertEquals(10, $this->queueWorkerSpy->getIterations());
    }

    public function testRunManageSuccessfulJob() {
        $this->queueWorkerSpy->setMaxIterations(10);
        $this->queueWorkerSpy->start();
        $this->assertEquals(10, $this->sourceQueueMock->getNextCounter, 'Get Next counter');
        $this->assertEquals(10, $this->sourceQueueMock->successfulCounter, 'Successful counter');
        $this->assertEquals(0, $this->sourceQueueMock->failedCounter, 'Failed counter');
        $this->assertEquals(0, $this->sourceQueueMock->errorCounter, 'Error counter');
        $this->assertEquals(0, $this->sourceQueueMock->nothingToDoCounter, 'Nothing to do counter');
    }

    public function testStopInstruction() {
        $this->sourceQueueMock = $this->getMock('SimplePhpQueue\Mocks\QueueSpy', array('getNext'));
        $this->sourceQueueMock->expects($this->at(0))->method('getNext')->willReturn(1);
        $this->sourceQueueMock->expects($this->at(1))->method('getNext')->willReturn(1);
        $this->sourceQueueMock->expects($this->at(2))->method('getNext')->willReturn('STOP');

        $this->queueWorkerSpy = new QueueWorkerSpy($this->sourceQueueMock, $this->taskHandlerMock);
        $this->queueWorkerSpy->setMaxIterations(10);
        $this->queueWorkerSpy->start();
        $this->assertEquals(3, $this->queueWorkerSpy->getIterations());
        $this->assertEquals(2, $this->sourceQueueMock->successfulCounter, 'Successful counter');
        $this->assertEquals(0, $this->sourceQueueMock->failedCounter, 'Failed counter');
        $this->assertEquals(0, $this->sourceQueueMock->errorCounter, 'Error counter');
        $this->assertEquals(0, $this->sourceQueueMock->nothingToDoCounter, 'Nothing to do counter');
    }

    public function testNothingToDo() {
        $this->sourceQueueMock = $this->getMock('SimplePhpQueue\Mocks\QueueSpy', array('getNext'));
        $this->sourceQueueMock->expects($this->at(0))->method('getNext')->willReturn(false);
        $this->sourceQueueMock->expects($this->at(1))->method('getNext')->willReturn(1);
        $this->sourceQueueMock->expects($this->at(2))->method('getNext')->willReturn(false);
        $this->sourceQueueMock->expects($this->at(3))->method('getNext')->willReturn(0);
        $this->sourceQueueMock->expects($this->at(4))->method('getNext')->willReturn('');

        $this->queueWorkerSpy = new QueueWorkerSpy($this->sourceQueueMock, $this->taskHandlerMock);
        $this->queueWorkerSpy->setMaxIterations(5);
        $this->queueWorkerSpy->start();
        $this->assertEquals(5, $this->queueWorkerSpy->getIterations());
        $this->assertEquals(3, $this->sourceQueueMock->successfulCounter, 'Successful counter');
        $this->assertEquals(0, $this->sourceQueueMock->failedCounter, 'Failed counter');
        $this->assertEquals(0, $this->sourceQueueMock->errorCounter, 'Error counter');
        $this->assertEquals(2, $this->sourceQueueMock->nothingToDoCounter, 'Nothing to do counter');
    }

    public function testRunManagedFailedJobs() {
        $this->taskHandlerMock = $this->getMock('SimplePhpQueue\Mocks\TaskSpy', array('manage'));
        $this->taskHandlerMock->expects($this->at(0))->method('manage')->willReturn(true);
        $this->taskHandlerMock->expects($this->at(1))->method('manage')->willReturn(false);
        $this->taskHandlerMock->expects($this->at(2))->method('manage')->willReturn(true);
        $this->taskHandlerMock->expects($this->at(3))->method('manage')->willReturn(true);
        $this->taskHandlerMock->expects($this->at(4))->method('manage')->willReturn(false);

        $this->queueWorkerSpy = new QueueWorkerSpy($this->sourceQueueMock, $this->taskHandlerMock);
        $this->queueWorkerSpy->setMaxIterations(5);
        $this->queueWorkerSpy->start();
        $this->assertEquals(5, $this->queueWorkerSpy->getIterations());
        $this->assertEquals(3, $this->sourceQueueMock->successfulCounter, 'Successful counter');
        $this->assertEquals(2, $this->sourceQueueMock->failedCounter, 'Failed counter');
        $this->assertEquals(0, $this->sourceQueueMock->errorCounter, 'Error counter');
        $this->assertEquals(0, $this->sourceQueueMock->nothingToDoCounter, 'Nothing to do counter');
    }

    public function testHandlerManageExceptions() {
        $this->taskHandlerMock = $this->getMock('SimplePhpQueue\Mocks\TaskSpy', array('manage'));
        $this->taskHandlerMock->expects($this->at(0))->method('manage')->willReturn(true);
        $this->taskHandlerMock->expects($this->at(1))->method('manage')->willThrowException(new \Exception('Testing exceptions'));
        $this->taskHandlerMock->expects($this->at(2))->method('manage')->willReturn(false);
        $this->taskHandlerMock->expects($this->at(3))->method('manage')->willThrowException(new \Exception('Testing exceptions'));

        $this->queueWorkerSpy = new QueueWorkerSpy($this->sourceQueueMock, $this->taskHandlerMock);
        $this->queueWorkerSpy->setMaxIterations(4);
        $this->queueWorkerSpy->start();
        $this->assertEquals(4, $this->queueWorkerSpy->getIterations());
        $this->assertEquals(1, $this->sourceQueueMock->successfulCounter, 'Successful counter');
        $this->assertEquals(1, $this->sourceQueueMock->failedCounter, 'Failed counter');
        $this->assertEquals(2, $this->sourceQueueMock->errorCounter, 'Error counter');
        $this->assertEquals(0, $this->sourceQueueMock->nothingToDoCounter, 'Nothing to do counter');
    }

    public function testSourceQueueGetNextExceptions() {
        $this->sourceQueueMock = $this->getMock('SimplePhpQueue\Mocks\QueueSpy', array('getNext'));
        $this->sourceQueueMock->expects($this->at(0))->method('getNext')->willThrowException(new \Exception('Testing exceptions'));
        $this->sourceQueueMock->expects($this->at(1))->method('getNext')->willReturn(1);
        $this->sourceQueueMock->expects($this->at(2))->method('getNext')->willThrowException(new \Exception('Testing exceptions'));

        $this->queueWorkerSpy = new QueueWorkerSpy($this->sourceQueueMock, $this->taskHandlerMock);
        $this->queueWorkerSpy->setMaxIterations(3);
        $this->queueWorkerSpy->start();
        $this->assertEquals(3, $this->queueWorkerSpy->getIterations());
        $this->assertEquals(1, $this->sourceQueueMock->successfulCounter, 'Successful counter');
        $this->assertEquals(0, $this->sourceQueueMock->failedCounter, 'Failed counter');
        $this->assertEquals(2, $this->sourceQueueMock->errorCounter, 'Error counter');
        $this->assertEquals(0, $this->sourceQueueMock->nothingToDoCounter, 'Nothing to do counter');
    }

    public function testSourceQueueSuccessfulAndFailedExceptions() {
        $this->taskHandlerMock = $this->getMock('SimplePhpQueue\Mocks\TaskSpy', array('manage'));
        $this->sourceQueueMock = $this->getMock('SimplePhpQueue\Mocks\QueueSpy', array('successful', 'failed'));
        $this->taskHandlerMock->expects($this->at(0))->method('manage')->willReturn(true);
        $this->sourceQueueMock->expects($this->at(0))->method('successful')->willThrowException(new \Exception('Testing exceptions'));
        $this->taskHandlerMock->expects($this->at(1))->method('manage')->willReturn(true);
        $this->sourceQueueMock->expects($this->at(1))->method('successful')->willReturn(1);
        $this->taskHandlerMock->expects($this->at(2))->method('manage')->willReturn(false);
        $this->sourceQueueMock->expects($this->at(2))->method('failed')->willThrowException(new \Exception('Testing exceptions'));
        $this->taskHandlerMock->expects($this->at(3))->method('manage')->willReturn(false);
        $this->sourceQueueMock->expects($this->at(3))->method('failed')->willReturn(1);

        $this->queueWorkerSpy = new QueueWorkerSpy($this->sourceQueueMock, $this->taskHandlerMock);
        $this->queueWorkerSpy->setMaxIterations(4);
        $this->queueWorkerSpy->start();
        $this->assertEquals(4, $this->queueWorkerSpy->getIterations());
        $this->assertEquals(2, $this->sourceQueueMock->errorCounter, 'Error counter');
        $this->assertEquals(0, $this->sourceQueueMock->nothingToDoCounter, 'Nothing to do counter');
    }

    public function testLoggerDebug() {
        $loggerSpy = new LoggerSpy();
        $this->queueWorkerSpy->setMaxIterations(1);
        $this->queueWorkerSpy->setLogger($loggerSpy);
        $this->queueWorkerSpy->start();
        $this->assertEquals(3, count($loggerSpy->debugMessages));

        $loggerSpy = new LoggerSpy();
        $this->queueWorkerSpy->setMaxIterations(3);
        $this->queueWorkerSpy->setLogger($loggerSpy);
        $this->queueWorkerSpy->start();
        $this->assertEquals(5, count($loggerSpy->debugMessages));
    }

    public function testLoggerError() {
        $loggerSpy = new LoggerSpy();
        $this->taskHandlerMock = $this->getMock('SimplePhpQueue\Mocks\TaskSpy', array('manage'));
        $this->taskHandlerMock->expects($this->at(0))->method('manage')->willReturn(true);
        $this->taskHandlerMock->expects($this->at(1))->method('manage')->willThrowException(new \Exception('Testing exceptions'));
        $this->taskHandlerMock->expects($this->at(2))->method('manage')->willReturn(false);
        $this->taskHandlerMock->expects($this->at(3))->method('manage')->willThrowException(new \Exception('Testing exceptions'));

        $this->queueWorkerSpy = new QueueWorkerSpy($this->sourceQueueMock, $this->taskHandlerMock);
        $this->queueWorkerSpy->setMaxIterations(4);
        $this->queueWorkerSpy->setLogger($loggerSpy);
        $this->queueWorkerSpy->start();
        $this->assertEquals(2, count($loggerSpy->errorMessages));
    }
}