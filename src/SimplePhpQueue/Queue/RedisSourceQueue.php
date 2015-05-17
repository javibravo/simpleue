<?php
/**
 * Created by PhpStorm.
 * User: jbravo
 * Date: 10/05/15
 * Time: 18:01
 */

namespace SimplePhpQueue\Queue;

use Predis\Client;

class RedisSourceQueue implements SourceQueue {

    private $redisClient;
    private $sourceQueue;
    private $maxWaitingSeconds;

    public function __construct(array $redisConnectionSettings, $queueName, $maxWaitingSeconds = 30) {
        $this->redisClient = $this->getRedisClient($redisConnectionSettings);
        $this->sourceQueue = $queueName;
        $this->maxWaitingSeconds = $maxWaitingSeconds;
    }

    public function getRedisClient($connectionSettings) {
        array_key_exists('host', $connectionSettings) ? : $connectionSettings['host'] = 'localhost';
        array_key_exists('port', $connectionSettings) ? : $connectionSettings['port'] = 6379;
        array_key_exists('database', $connectionSettings) ? : $connectionSettings['host'] = 0;
        $connectionSettings['scheme'] = 'tcp';
        return new Client($connectionSettings);
    }

    public function getNext() {
        return $this->redisClient->brpoplpush($this->getSourceQueue(), $this->getProcessingQueue(), $this->maxWaitingSeconds);
    }

    public function successful($task) {
        $this->redisClient->lrem($this->getProcessingQueue(), 1, $task);
        return;
    }

    public function failed($task) {
        $this->redisClient->lpush($this->getFailedQueue(), $task);
        $this->redisClient->lrem($this->getProcessingQueue(), 1, $task);
        return;
    }

    public function error($task) {
        $this->redisClient->lpush($this->getErrorQueue(), $task);
        $this->redisClient->lrem($this->getProcessingQueue(), 1, $task);
        return;
    }

    public function nothingToDo() {
        $this->redisClient->ping();
    }

    public function getSourceQueue() {
        return $this->sourceQueue;
    }

    public function getProcessingQueue() {
        return $this->sourceQueue . ":processing";
    }

    public function getFailedQueue() {
        return $this->sourceQueue . ":failed";
    }

    public function getErrorQueue() {
        return $this->sourceQueue . ":error";
    }

}