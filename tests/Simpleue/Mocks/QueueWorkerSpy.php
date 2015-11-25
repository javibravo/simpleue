<?php
/**
 * User: Javier Bravo
 * Date: 10/05/15
 */

namespace Simpleue\Mocks;

use Simpleue\Worker\QueueWorker;

class QueueWorkerSpy extends QueueWorker {

    public function getIterations() {
        return $this->iterations;
    }

}