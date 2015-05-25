<?php
/**
 * Created by PhpStorm.
 * User: jbravo
 * Date: 10/05/15
 * Time: 18:08
 */

namespace SimplePhpQueue\Task;


interface Task {
    public function manage($task);
}