<?php

namespace Simpleue\Locker;

class MemcachedLocker extends BaseLocker
{
    /**
     * @var \Memcached;
     */
    private $memcached;

    public function __construct(\Memcached $memcached)
    {
        parent::__construct();

        $this->memcached = $memcached;
        $this->memcached->setOptions([
            \Memcached::OPT_TCP_NODELAY => true,
            \Memcached::OPT_NO_BLOCK => true,
            \Memcached::OPT_CONNECT_TIMEOUT => 60
        ]);
    }

    public function getLockerInfo()
    {
        return 'Memcached ( ' . json_encode($this->memcached->getServerList()) . ' )';
    }

    public function lock($job, $timeout = 30)
    {
        if (!$job) {
            throw new \RuntimeException('Job for lock is invalid!');
        }
        return $this->memcached->add(
            $this->getJobUniqId($job),
            time() + $timeout + 1,
            $timeout
        );
    }

    public function disconnect()
    {
        $this->memcached->quit();
    }
}
