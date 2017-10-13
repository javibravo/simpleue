<?php
/**
 * Javier Bravo
 * Date: 10/05/15
 */

namespace Simpleue\Mocks;

use Simpleue\Job\Job;

class JobSpy implements Job {

    private $manageCounter;
    private $quitCount;
    private $testSignal;

    public function _construct() {
        $this->manageCounter = 0;
    }

    public function manage($job) {
        if ($this->quitCount && (($this->manageCounter+1) === $this->quitCount)) {
            posix_kill(posix_getpid(), $this->testSignal);
        }

        $this->manageCounter++;
        return true;
    }

    public function isStopJob($job) {
        return ($job === 'STOP');
    }

    public function isValidJob($job) {
        return ($job !== false);
    }

    public function setQuitCount($num) {
        $this->quitCount = $num;
    }

    public function setSignalToTest($sig) {
        $this->testSignal = $sig;
    }

    public function getManageCounter() {
        return $this->manageCounter;
    }
}
