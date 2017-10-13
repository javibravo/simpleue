<?php
/**
 * User: Javier Bravo
 * Date: 10/05/15
 */

namespace Simpleue\Unitary\Worker;

use Simpleue\Mocks\QueueWorkerSpy;
use Simpleue\Mocks\QueueSpy;
use Simpleue\Mocks\JobSpy;
use Simpleue\Mocks\LoggerSpy;

class QueueWorkerTest extends \PHPUnit_Framework_TestCase {

    private $queueWorkerSpy;
    private $sourceQueueMock;
    private $jobHandlerMock;

    protected function setUp() {
        date_default_timezone_set('Europe/London');

        $this->sourceQueueMock = new QueueSpy();
        $this->jobHandlerMock = new JobSpy();
        $this->queueWorkerSpy = new QueueWorkerSpy($this->sourceQueueMock, $this->jobHandlerMock);
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
        $this->assertEquals(0, $this->sourceQueueMock->stoppedCounter, 'Stop inst. management counter');
        $this->assertEquals(20, $this->sourceQueueMock->getMessageBodyCounter, 'Message body counter');
    }

    public function testStopInstruction() {
        $this->sourceQueueMock = $this->getMock('Simpleue\Mocks\QueueSpy', array('getNext'));
        $this->sourceQueueMock->expects($this->at(0))->method('getNext')->willReturn(1);
        $this->sourceQueueMock->expects($this->at(1))->method('getNext')->willReturn(1);
        $this->sourceQueueMock->expects($this->at(2))->method('getNext')->willReturn('STOP');

        $this->queueWorkerSpy = new QueueWorkerSpy($this->sourceQueueMock, $this->jobHandlerMock);
        $this->queueWorkerSpy->setMaxIterations(10);
        $this->queueWorkerSpy->start();
        $this->assertEquals(3, $this->queueWorkerSpy->getIterations());
        $this->assertEquals(2, $this->sourceQueueMock->successfulCounter, 'Successful counter');
        $this->assertEquals(0, $this->sourceQueueMock->failedCounter, 'Failed counter');
        $this->assertEquals(0, $this->sourceQueueMock->errorCounter, 'Error counter');
        $this->assertEquals(0, $this->sourceQueueMock->nothingToDoCounter, 'Nothing to do counter');
        $this->assertEquals(1, $this->sourceQueueMock->stoppedCounter, 'Stop inst. management counter');
        $this->assertEquals(5, $this->sourceQueueMock->getMessageBodyCounter, 'Message body counter');
    }

    public function testNothingToDo() {
        $this->sourceQueueMock = $this->getMock('Simpleue\Mocks\QueueSpy', array('getNext'));
        $this->sourceQueueMock->expects($this->at(0))->method('getNext')->willReturn(false);
        $this->sourceQueueMock->expects($this->at(1))->method('getNext')->willReturn(1);
        $this->sourceQueueMock->expects($this->at(2))->method('getNext')->willReturn(false);
        $this->sourceQueueMock->expects($this->at(3))->method('getNext')->willReturn(0);
        $this->sourceQueueMock->expects($this->at(4))->method('getNext')->willReturn('');

        $this->jobHandlerMock = $this->getMock('Simpleue\Mocks\JobSpy', array('isValidJob'));
        $this->jobHandlerMock->expects($this->at(0))->method('isValidJob')->willReturn(true);
        $this->jobHandlerMock->expects($this->at(1))->method('isValidJob')->willReturn(false);
        $this->jobHandlerMock->expects($this->at(2))->method('isValidJob')->willReturn(true);
        $this->jobHandlerMock->expects($this->at(3))->method('isValidJob')->willReturn(true);
        $this->jobHandlerMock->expects($this->at(4))->method('isValidJob')->willReturn(true);

        $this->queueWorkerSpy = new QueueWorkerSpy($this->sourceQueueMock, $this->jobHandlerMock);
        $this->queueWorkerSpy->setMaxIterations(5);
        $this->queueWorkerSpy->start();
        $this->assertEquals(5, $this->queueWorkerSpy->getIterations());
        $this->assertEquals(2, $this->sourceQueueMock->successfulCounter, 'Successful counter');
        $this->assertEquals(0, $this->sourceQueueMock->failedCounter, 'Failed counter');
        $this->assertEquals(0, $this->sourceQueueMock->errorCounter, 'Error counter');
        $this->assertEquals(3, $this->sourceQueueMock->nothingToDoCounter, 'Nothing to do counter');
        $this->assertEquals(0, $this->sourceQueueMock->stoppedCounter, 'Stop inst. management counter');
        $this->assertEquals(6, $this->sourceQueueMock->getMessageBodyCounter, 'Message body counter');
    }

    public function testRunManagedFailedJobs() {
        $this->jobHandlerMock = $this->getMock('Simpleue\Mocks\JobSpy', array('manage'));
        $this->jobHandlerMock->expects($this->at(0))->method('manage')->willReturn(true);
        $this->jobHandlerMock->expects($this->at(1))->method('manage')->willReturn(false);
        $this->jobHandlerMock->expects($this->at(2))->method('manage')->willReturn(true);
        $this->jobHandlerMock->expects($this->at(3))->method('manage')->willReturn(true);
        $this->jobHandlerMock->expects($this->at(4))->method('manage')->willReturn(false);

        $this->queueWorkerSpy = new QueueWorkerSpy($this->sourceQueueMock, $this->jobHandlerMock);
        $this->queueWorkerSpy->setMaxIterations(5);
        $this->queueWorkerSpy->start();
        $this->assertEquals(5, $this->queueWorkerSpy->getIterations());
        $this->assertEquals(3, $this->sourceQueueMock->successfulCounter, 'Successful counter');
        $this->assertEquals(2, $this->sourceQueueMock->failedCounter, 'Failed counter');
        $this->assertEquals(0, $this->sourceQueueMock->errorCounter, 'Error counter');
        $this->assertEquals(0, $this->sourceQueueMock->nothingToDoCounter, 'Nothing to do counter');
        $this->assertEquals(0, $this->sourceQueueMock->stoppedCounter, 'Stop inst. management counter');
        $this->assertEquals(10, $this->sourceQueueMock->getMessageBodyCounter, 'Message body counter');
    }

    public function testHandlerManageExceptions() {
        $this->jobHandlerMock = $this->getMock('Simpleue\Mocks\JobSpy', array('manage'));
        $this->jobHandlerMock->expects($this->at(0))->method('manage')->willReturn(true);
        $this->jobHandlerMock->expects($this->at(1))->method('manage')->willThrowException(new \Exception('Testing exceptions'));
        $this->jobHandlerMock->expects($this->at(2))->method('manage')->willReturn(false);
        $this->jobHandlerMock->expects($this->at(3))->method('manage')->willThrowException(new \Exception('Testing exceptions'));

        $this->queueWorkerSpy = new QueueWorkerSpy($this->sourceQueueMock, $this->jobHandlerMock);
        $this->queueWorkerSpy->setMaxIterations(4);
        $this->queueWorkerSpy->start();
        $this->assertEquals(4, $this->queueWorkerSpy->getIterations());
        $this->assertEquals(1, $this->sourceQueueMock->successfulCounter, 'Successful counter');
        $this->assertEquals(1, $this->sourceQueueMock->failedCounter, 'Failed counter');
        $this->assertEquals(2, $this->sourceQueueMock->errorCounter, 'Error counter');
        $this->assertEquals(0, $this->sourceQueueMock->nothingToDoCounter, 'Nothing to do counter');
        $this->assertEquals(0, $this->sourceQueueMock->stoppedCounter, 'Stop inst. management counter');
        $this->assertEquals(8, $this->sourceQueueMock->getMessageBodyCounter, 'Message body counter');
    }

    public function testSourceQueueGetNextExceptions() {
        $this->sourceQueueMock = $this->getMock('Simpleue\Mocks\QueueSpy', array('getNext'));
        $this->sourceQueueMock->expects($this->at(0))->method('getNext')->willThrowException(new \Exception('Testing exceptions'));
        $this->sourceQueueMock->expects($this->at(1))->method('getNext')->willReturn(1);
        $this->sourceQueueMock->expects($this->at(2))->method('getNext')->willThrowException(new \Exception('Testing exceptions'));

        $this->queueWorkerSpy = new QueueWorkerSpy($this->sourceQueueMock, $this->jobHandlerMock);
        $this->queueWorkerSpy->setMaxIterations(3);
        $this->queueWorkerSpy->start();
        $this->assertEquals(3, $this->queueWorkerSpy->getIterations());
        $this->assertEquals(1, $this->sourceQueueMock->successfulCounter, 'Successful counter');
        $this->assertEquals(0, $this->sourceQueueMock->failedCounter, 'Failed counter');
        $this->assertEquals(2, $this->sourceQueueMock->errorCounter, 'Error counter');
        $this->assertEquals(0, $this->sourceQueueMock->nothingToDoCounter, 'Nothing to do counter');
        $this->assertEquals(0, $this->sourceQueueMock->stoppedCounter, 'Stop inst. management');
        $this->assertEquals(2, $this->sourceQueueMock->getMessageBodyCounter, 'Message body counter');
    }

    public function testSourceQueueSuccessfulAndFailedExceptions() {
        $this->jobHandlerMock = $this->getMock('Simpleue\Mocks\JobSpy', array('manage'));
        $this->sourceQueueMock = $this->getMock('Simpleue\Mocks\QueueSpy', array('successful', 'failed'));
        $this->jobHandlerMock->expects($this->at(0))->method('manage')->willReturn(true);
        $this->sourceQueueMock->expects($this->at(0))->method('successful')->willThrowException(new \Exception('Testing exceptions'));
        $this->jobHandlerMock->expects($this->at(1))->method('manage')->willReturn(true);
        $this->sourceQueueMock->expects($this->at(1))->method('successful')->willReturn(1);
        $this->jobHandlerMock->expects($this->at(2))->method('manage')->willReturn(false);
        $this->sourceQueueMock->expects($this->at(2))->method('failed')->willThrowException(new \Exception('Testing exceptions'));
        $this->jobHandlerMock->expects($this->at(3))->method('manage')->willReturn(false);
        $this->sourceQueueMock->expects($this->at(3))->method('failed')->willReturn(1);

        $this->queueWorkerSpy = new QueueWorkerSpy($this->sourceQueueMock, $this->jobHandlerMock);
        $this->queueWorkerSpy->setMaxIterations(4);
        $this->queueWorkerSpy->start();
        $this->assertEquals(4, $this->queueWorkerSpy->getIterations());
        $this->assertEquals(2, $this->sourceQueueMock->errorCounter, 'Error counter');
        $this->assertEquals(0, $this->sourceQueueMock->nothingToDoCounter, 'Nothing to do counter');
        $this->assertEquals(0, $this->sourceQueueMock->stoppedCounter, 'Stop inst. management counter');
        $this->assertEquals(8, $this->sourceQueueMock->getMessageBodyCounter, 'Message body counter');
    }

    public function testWorkerExitsGracefullyOnSigINT() {
        if (!function_exists('pcntl_signal')) {
            $this->markTestSkipped('Enable pcntl_* extension to run this test');
        }

        if (defined('HHVM_VERSION')) {
            $this->markTestSkipped('The Graceful Exit feature does not work for HHVM');
        }

        $this->jobHandlerMock->setQuitCount(3);
        $this->jobHandlerMock->setSignalToTest(SIGINT);
        $this->queueWorkerSpy = new QueueWorkerSpy($this->sourceQueueMock, $this->jobHandlerMock, 10, true);
        $this->queueWorkerSpy->start();
        $this->assertEquals(3, $this->queueWorkerSpy->getIterations());
        $this->assertEquals(3, $this->jobHandlerMock->getmanageCounter());
    }

    public function testWorkerExitsGracefullyOnSigTERM() {
        if (!function_exists('pcntl_signal')) {
            $this->markTestSkipped('Enable pcntl_* extension to run this test');
        }

        if (defined('HHVM_VERSION')) {
            $this->markTestSkipped('The Graceful Exit feature does not work for HHVM');
        }

        $this->jobHandlerMock->setQuitCount(8);
        $this->jobHandlerMock->setSignalToTest(SIGTERM);
        $this->queueWorkerSpy = new QueueWorkerSpy($this->sourceQueueMock, $this->jobHandlerMock, 10, true);
        $this->queueWorkerSpy->start();
        $this->assertEquals(8, $this->queueWorkerSpy->getIterations());
        $this->assertEquals(8, $this->jobHandlerMock->getmanageCounter());
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
        $this->jobHandlerMock = $this->getMock('Simpleue\Mocks\JobSpy', array('manage'));
        $this->jobHandlerMock->expects($this->at(0))->method('manage')->willReturn(true);
        $this->jobHandlerMock->expects($this->at(1))->method('manage')->willThrowException(new \Exception('Testing exceptions'));
        $this->jobHandlerMock->expects($this->at(2))->method('manage')->willReturn(false);
        $this->jobHandlerMock->expects($this->at(3))->method('manage')->willThrowException(new \Exception('Testing exceptions'));

        $this->queueWorkerSpy = new QueueWorkerSpy($this->sourceQueueMock, $this->jobHandlerMock);
        $this->queueWorkerSpy->setMaxIterations(4);
        $this->queueWorkerSpy->setLogger($loggerSpy);
        $this->queueWorkerSpy->start();
        $this->assertEquals(2, count($loggerSpy->errorMessages));
    }
}
