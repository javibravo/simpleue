<?php
/**
 * User: Javier Bravo
 * Date: 10/05/15
 */

namespace SimplePhpQueue\Queue;

use Predis\Client;

class RedisQueue implements Queue {

    private $redisClient;
    private $sourceQueue;
    private $maxWaitingSeconds;

    public function __construct(Client $redisClient, $queueName, $maxWaitingSeconds = 30) {
        $this->redisClient = $redisClient;
        $this->sourceQueue = $queueName;
        $this->maxWaitingSeconds = $maxWaitingSeconds;
    }

    public function setRedisClient(Client $redisClient) {
        $this->redisClient = $redisClient;
        return;
    }

    public function getNext() {
        $queueItem = $this->redisClient->brpoplpush($this->getSourceQueue(), $this->getProcessingQueue(), $this->maxWaitingSeconds);
        return ($queueItem !== null) ? $queueItem : false;
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

    public function stopped($task) {
        $this->redisClient->lrem($this->getProcessingQueue(), 1, $task);
        return;
    }

    public function getSourceQueue() {
        return $this->sourceQueue;
    }

    protected function getProcessingQueue() {
        return $this->sourceQueue . ":processing";
    }

    protected function getFailedQueue() {
        return $this->sourceQueue . ":failed";
    }

    protected function getErrorQueue() {
        return $this->sourceQueue . ":error";
    }

}