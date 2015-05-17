<?php
/**
 * Created by PhpStorm.
 * User: jbravo
 * Date: 10/05/15
 * Time: 18:01
 */

namespace SimplePhpQueue\Queue;


interface SourceQueue {

    public function getNext();
    public function successful($task);
    public function failed($task);
    public function error($task);
    public function nothingToDo();

}