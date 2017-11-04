<?php

namespace Simpleue\Locker;

interface LockerInterface
{
    public function getLockerInfo();
    public function lock($job, $timeout = 40);
    public function disconnect();
}
