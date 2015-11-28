<?php
/**
 * Javier Bravo
 * Date: 10/05/15
 */

namespace Simpleue\Mocks;

use Simpleue\Job\Job;

class JobSpy implements Job {

    private $manageCounter;

    public function _construct() {
        $this->manageCounter = 0;
    }

    public function manage($job) {
        $this->manageCounter++;
        return true;
    }

    public function isStopJob($job) {
        return ($job === 'STOP');
    }

}