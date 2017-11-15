<?php

namespace Simpleue\Locker;

interface Locker
{
    public function getLockerInfo();
    public function lock($job, $timeout = 40);
    public function disconnect();
}
