<?php

namespace Simpleue\Locker;

abstract class LockerAbstract implements LockerInterface
{
    protected $uniqIdFunction;

    public function __construct()
    {
        $this->uniqIdFunction = function ($job) {
            return md5(strtolower($job));
        };
    }

    public function setJobUniqIdFunction(\Closure $function)
    {
        $this->uniqIdFunction = $function;
    }

    public function getJobUniqId($job)
    {
        if ($this->uniqIdFunction) {
            $func = $this->uniqIdFunction;
            return $func($job);
        } else {
            throw new \InvalidArgumentException('Locker::uniqIdFunction not defined!');
        }
    }
}
