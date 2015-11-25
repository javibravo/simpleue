<?php
/**
 * User: Javier Bravo
 * Date: 10/05/15
 */

namespace Simpleue\Mocks;

use Simpleue\Queue\Queue;

class QueueSpy implements Queue {

    public $getNextCounter;
    public $successfulCounter;
    public $failedCounter;
    public $errorCounter;
    public $nothingToDoCounter;
    public $stoppedCounter;
    public $getMessageBodyCounter;

    public function _construct() {
        $this->getNextCounter = 0;
        $this->successfulCounter = 0;
        $this->failedCounter = 0;
        $this->errorCounter = 0;
        $this->nothingToDoCounter = 0;
        $this->stoppedCounter = 0;
    }

    public function getNext() {
        $this->getNextCounter++;
        return rand(0,1000);
    }

    public function successful($job) {
        $this->successfulCounter++;
        return;
    }

    public function failed($job) {
        $this->failedCounter++;
        return $job;
    }

    public function error($job) {
        $this->errorCounter++;
        return $job;
    }

    public function nothingToDo() {
        $this->nothingToDoCounter++;
        return;
    }

    public function stopped($job) {
        $this->stoppedCounter++;
        return;
    }

    public function getMessageBody($job) {
        $this->getMessageBodyCounter++;
        return $job;
    }

    public function toString($job) {
        return $job;
    }

    public function sendJob($job) {
        return;
    }

}