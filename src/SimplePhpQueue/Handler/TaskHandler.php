<?php
/**
 * Created by PhpStorm.
 * User: jbravo
 * Date: 10/05/15
 * Time: 18:08
 */

namespace SimplePhpQueue\Handler;


interface TaskHandler {
    public function manage();
}