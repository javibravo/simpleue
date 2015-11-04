<?php
/**
 * User: Javier Bravo
 * Date: 10/05/15
 */

namespace SimplePhpQueue\Mocks;

use SimplePhpQueue\Queue\Queue;

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

    public function successful($task) {
        $this->successfulCounter++;
        return;
    }

    public function failed($task) {
        $this->failedCounter++;
        return $task;
    }

    public function error($task) {
        $this->errorCounter++;
        return $task;
    }

    public function nothingToDo() {
        $this->nothingToDoCounter++;
        return;
    }

    public function stopped($task) {
        $this->stoppedCounter++;
        return;
    }

    public function getMessageBody($task) {
        $this->getMessageBodyCounter++;
        return $task;
    }

}