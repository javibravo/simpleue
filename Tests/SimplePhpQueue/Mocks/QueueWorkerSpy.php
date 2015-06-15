<?php
/**
 * Created by PhpStorm.
 * User: jbravo
 * Date: 10/05/15
 * Time: 18:32
 */

namespace SimplePhpQueue\Mocks;

use SimplePhpQueue\Worker\QueueWorker;

class QueueWorkerSpy extends QueueWorker {

    public function getIterations() {
        return $this->iterations;
    }

}