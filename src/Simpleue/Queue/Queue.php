<?php
/**
 * User: Javier Bravo
 * Date: 10/05/15
 */

namespace Simpleue\Queue;


interface Queue {

    public function getNext();
    public function successful($job);
    public function failed($job);
    public function error($job);
    public function nothingToDo();
    public function stopped($job);
    public function getMessageBody($job);
    public function toString($job);
    public function sendJob($job);

}