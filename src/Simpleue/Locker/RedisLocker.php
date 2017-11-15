<?php

namespace Simpleue\Locker;

class RedisLocker extends BaseLocker
{
    /**
     * @var \Redis;
     */
    private $redis;

    public function __construct(\Redis $redisClient)
    {
        parent::__construct();

        $this->redis = $redisClient;
        if ($this->redis->isConnected()===false) {
            throw new \RuntimeException('Redis Client not connected!');
        }
    }

    public function getLockerInfo()
    {
        return 'Redis ( '
            . $this->redis->getHost()
            . ':' . $this->redis->getPort()
            . ' -> ' . $this->redis->getDbNum()
            . ' )';
    }

    public function lock($job, $timeout = 40)
    {
        if (!$job) {
            throw new \RuntimeException('Job for lock is invalid!');
        }
        $key    = $this->getJobUniqId($job);
        $status = $this->redis->set(
            $key,
            time() + $timeout + 1,
            array('nx', 'ex' => $timeout)
        );
        if ($status) {
            return true;
        }

        $currentLockTimestamp = $this->redis->get($key);
        if ($currentLockTimestamp > time()) {
            return false;
        }
        $oldLockTimestamp = $this->redis->getSet($key, (time() + $timeout + 1));
        if ($oldLockTimestamp > time()) {
            return false;
        }
        return true;
    }

    public function disconnect()
    {
        $this->redis->close();
    }
}
