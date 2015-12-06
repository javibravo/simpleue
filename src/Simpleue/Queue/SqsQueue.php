<?php
/**
 * User: Javier Bravo
 * Date: 10/05/15
 */

namespace Simpleue\Queue;

use Aws\Sqs\Exception\SqsException;
use Aws\Sqs\SqsClient;

/*
 * AWS API 3.x doc : http://docs.aws.amazon.com/aws-sdk-php/v3/api/
 */
class SqsQueue implements Queue {

    private $sqsClient;
    private $sourceQueueUrl;
    private $failedQueueUrl;
    private $errorQueueUrl;
    private $maxWaitingSeconds;
    private $visibilityTimeout;

    public function __construct(SqsClient $sqsClient, $queueName, $maxWaitingSeconds = 20, $visibilityTimeout = 30) {
        $this->sqsClient = $sqsClient;
        $this->maxWaitingSeconds = $maxWaitingSeconds;
        $this->visibilityTimeout = $visibilityTimeout;
        $this->setQueues($queueName);
    }

    public function setSourceQueueUrl($queueUrl) {
        $this->sourceQueueUrl = $queueUrl;
    }

    public function setFailedQueueUrl($queueUrl) {
        $this->failedQueueUrl = $queueUrl;
    }

    public function setErrorQueueUrl($queueUrl) {
        $this->errorQueueUrl = $queueUrl;
    }

    protected function setQueues($queueName) {
        $this->sourceQueueUrl = $this->getQueueUrl($queueName);
        $this->failedQueueUrl = $this->getQueueUrl($queueName.'-failed');
        $this->errorQueueUrl = $this->getQueueUrl($queueName.'-error');
    }

    protected function getQueueUrl($queueName) {
        try {
            $queueData = $this->sqsClient->getQueueUrl(['QueueName' => $queueName]);
        } catch (SqsException $ex) {
            throw $ex;
        }
        return $queueData->get('QueueUrl');
    }

    public function setSqsClient(SqsClient $sqsClient) {
        $this->sqsClient = $sqsClient;
        return;
    }

    public function getNext() {
        $queueItem = $this->sqsClient->receiveMessage([
            'QueueUrl' => $this->sourceQueueUrl,
            'MaxNumberOfMessages' => 1,
            'WaitTimeSeconds' => $this->maxWaitingSeconds,
            'VisibilityTimeout' => $this->visibilityTimeout
        ]);
        if ($queueItem->hasKey('Messages')) {
            return $queueItem->get('Messages')[0];
        }
        return false;
    }

    public function successful($job) {
        $this->deleteMessage($this->sourceQueueUrl, $job['ReceiptHandle']);
    }

    protected function deleteMessage($queueUrl, $messageReceiptHandle) {
        $this->sqsClient->deleteMessage([
            'QueueUrl' => $queueUrl,
            'ReceiptHandle' => $messageReceiptHandle
        ]);
    }

    public function failed($job) {
        $this->sendMessage($this->failedQueueUrl, $job['Body']);
        $this->deleteMessage($this->sourceQueueUrl, $job['ReceiptHandle']);
        return;
    }

    private function sendMessage($queueUrl, $messageBody) {
        $this->sqsClient->sendMessage([
            'QueueUrl' => $queueUrl,
            'MessageBody' => $messageBody
        ]);
    }

    public function error($job) {
        $this->sendMessage($this->errorQueueUrl, $job['Body']);
        $this->deleteMessage($this->sourceQueueUrl, $job['ReceiptHandle']);
        return;
    }

    public function nothingToDo() {
        return;
    }

    public function stopped($job) {
        $this->deleteMessage($this->sourceQueueUrl, $job['ReceiptHandle']);
        return;
    }

    public function getMessageBody($job) {
        return $job['Body'];
    }

    public function toString($job) {
        return json_encode($job);
    }

    public function sendJob($job) {
        $this->sendMessage($this->sourceQueueUrl, $job);
    }
}