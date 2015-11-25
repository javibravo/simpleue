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

    public function mustStop($job) {
        return ($job === 'STOP');
    }

}