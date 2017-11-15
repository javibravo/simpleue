<?php

namespace Simpleue\Locker;

abstract class BaseLocker implements Locker
{
    protected $uniqIdFunction;
    protected $keyPrefix = 'sqslocker-';

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
            return $this->keyPrefix . $func($job);
        } else {
            throw new \InvalidArgumentException('Locker::uniqIdFunction not defined!');
        }
    }
}
