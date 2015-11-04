<?php
/**
 * User: Javier Bravo
 * Date: 10/05/15
 */

namespace SimplePhpQueue\Task;


interface Task {
    public function manage($task);
    public function toString($task);
}