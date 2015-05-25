<?php
/**
 * Created by PhpStorm.
 * User: jbravo
 * Date: 10/05/15
 * Time: 18:32
 */

namespace Tests\Mocks;

use SimplePhpQueue\Task\Task;

class TaskSpy implements Task {

    private $manageCounter;

    public function _construct() {
        $this->manageCounter = 0;
    }

    public function manage() {
        $this->manageCounter++;
        return true;
    }

}