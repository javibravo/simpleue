<?php
/**
 * User: Javier Bravo
 * Date: 10/05/15
 */

namespace SimplePhpQueue\Queue;

use Aws\Sqs\Exception\SqsException;
use Aws\Sqs\SqsClient;

/*
 * AWS API 3.x doc : http://docs.aws.amazon.com/aws-sdk-php/v3/api/
 */
class AwsSqsQueue implements Queue {

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

    public function successful($task) {
        $this->deleteMessage($this->sourceQueueUrl, $task['ReceiptHandle']);
    }

    protected function deleteMessage($queueUrl, $messageReceiptHandle) {
        $this->sqsClient->deleteMessage([
            'QueueUrl' => $queueUrl,
            'ReceiptHandle' => $messageReceiptHandle
        ]);
    }

    public function failed($task) {
        $this->sendMessage($this->failedQueueUrl, $task['Body']);
        $this->deleteMessage($this->sourceQueueUrl, $task['ReceiptHandle']);
        return;
    }

    private function sendMessage($queueUrl, $messageBody) {
        $this->sqsClient->sendMessage([
            'QueueUrl' => $queueUrl,
            'MessageBody' => $messageBody
        ]);
    }

    public function error($task) {
        $this->sendMessage($this->errorQueueUrl, $task['Body']);
        $this->deleteMessage($this->sourceQueueUrl, $task['ReceiptHandle']);
        return;
    }

    public function nothingToDo() {
        return;
    }

    public function stopped($task) {
        $this->deleteMessage($this->sourceQueueUrl, $task['ReceiptHandle']);
        return;
    }

    public function getMessageBody($task) {
        return $task['Body'];
    }

    public function toString($task) {
        return json_encode($task);
    }

    public function sendTask($task) {
        $this->sendMessage($this->sourceQueueUrl, $task);
    }
}