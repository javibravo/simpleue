<?php

namespace Simpleue\Queue;

use Pheanstalk\Job;
use Pheanstalk\Pheanstalk;

/**
 * Class BeanstalkdQueue
 * @author Adeyemi Olaoye <yemexx1@gmail.com>
 * @package Simpleue\Queue
 */
class BeanStalkdQueue implements Queue
{
    /** @var  Pheanstalk */
    private $beanStalkdClient;
    private $sourceQueue;
    private $failedQueue;
    private $errorQueue;

    public function __construct($beanStalkdClient, $queueName)
    {
        $this->beanStalkdClient = $beanStalkdClient;
        $this->setQueues($queueName);
    }


    protected function setQueues($queueName)
    {
        $this->sourceQueue = $queueName;
        $this->failedQueue = $queueName . '-failed';
        $this->errorQueue = $queueName . '-error';
    }

    public function getNext()
    {
        $this->beanStalkdClient->useTube($this->sourceQueue);
        return $this->beanStalkdClient->reserve();
    }

    public function successful($job)
    {
        return $this->beanStalkdClient->delete($job);
    }

    /**
     * @param $job Job
     * @return int
     */
    public function failed($job)
    {
        $this->beanStalkdClient->putInTube($this->failedQueue, $job->getData());
        $this->beanStalkdClient->delete($job);
        return;
    }

    /**
     * @param $job Job
     * @return int
     */
    public function error($job)
    {
        $this->beanStalkdClient->putInTube($this->errorQueue, $job->getData());
        $this->beanStalkdClient->delete($job);
        return;
    }

    public function nothingToDo()
    {
        return;
    }

    public function stopped($job)
    {
        return $this->beanStalkdClient->delete($job);
    }

    /**
     * @param $job Job
     * @return string
     */
    public function getMessageBody($job)
    {
        return $job->getData();
    }

    /**
     * @param $job Job
     * @return string
     */
    public function toString($job)
    {
        return json_encode(['id' => $job->getId(), 'data' => $job->getData()]);
    }

    /**
     * @param $job Job
     * @return int
     */
    public function sendJob($job)
    {
        return $this->beanStalkdClient->putInTube($this->sourceQueue, $job);
    }
}
