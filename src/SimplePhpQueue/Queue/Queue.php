<?php
/**
 * User: Javier Bravo
 * Date: 10/05/15
 */

namespace SimplePhpQueue\Queue;


interface Queue {

    public function getNext();
    public function successful($task);
    public function failed($task);
    public function error($task);
    public function nothingToDo();

}