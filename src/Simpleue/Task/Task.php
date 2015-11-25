<?php
/**
 * User: Javier Bravo
 * Date: 10/05/15
 */

namespace Simpleue\Task;


interface Task {
    public function manage($task);
    public function mustStop($task);
}