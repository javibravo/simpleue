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

    private function setQueues($queueName) {
        $this->sourceQueueUrl = $this->getQueueUrl($queueName);
        $this->failedQueueUrl = $this->getQueueUrl($queueName.'-failed');
        $this->errorQueueUrl = $this->getQueueUrl($queueName.'-error');
    }

    private function getQueueUrl($queueName) {
        try {
            $queueData = $this->sqsClient->getQueueUrl(['QueueName' => $queueName]);
        } catch (SqsException $ex) {
            $queueData = $this->sqsClient->createQueue(['QueueName' => $queueName]);
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
        $this->sqsClient->deleteMessage([
            'QueueUrl' => $this->sourceQueueUrl,
            'ReceiptHandle' => $task['ReceiptHandle']
        ]);
        return;
    }

    public function failed($task) {
        $this->sqsClient->sendMessage([
            'QueueUrl' => $this->failedQueueUrl,
            'MessageBody' => $task['Body']
        ]);

        $this->sqsClient->deleteMessage([
            'QueueUrl' => $this->sourceQueueUrl,
            'ReceiptHandle' => $task['ReceiptHandle']
        ]);
        return;
    }

    public function error($task) {
        $this->sqsClient->sendMessage([
            'QueueUrl' => $this->errorQueueUrl,
            'MessageBody' => $task['Body']
        ]);

        $this->sqsClient->deleteMessage([
            'QueueUrl' => $this->sourceQueueUrl,
            'ReceiptHandle' => $task['ReceiptHandle']
        ]);
        return;
    }

    public function nothingToDo() {
        return;
    }

}