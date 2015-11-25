<?php
/**
 * User: Javier Bravo
 * Date: 10/05/15
 */

namespace Simpleue\Queue;


interface Queue {

    public function getNext();
    public function successful($task);
    public function failed($task);
    public function error($task);
    public function nothingToDo();
    public function stopped($task);
    public function getMessageBody($task);
    public function toString($task);
    public function sendTask($task);

}