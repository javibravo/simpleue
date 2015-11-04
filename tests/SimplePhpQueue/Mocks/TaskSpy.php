<?php
/**
 * Javier Bravo
 * Date: 10/05/15
 */

namespace SimplePhpQueue\Mocks;

use SimplePhpQueue\Task\Task;

class TaskSpy implements Task {

    private $manageCounter;

    public function _construct() {
        $this->manageCounter = 0;
    }

    public function manage($task) {
        $this->manageCounter++;
        return true;
    }

    public function toString($task) {
        return $task;
    }

    public function isStopInstruction($task) {
        return ($task === 'STOP');
    }

}