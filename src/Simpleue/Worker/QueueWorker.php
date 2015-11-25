<?php
/**
 * User: Javier Bravo
 * Date: 9/01/15
 */

namespace Simpleue\Worker;

use Simpleue\Queue\Queue;
use Simpleue\Task\Task;
use Psr\Log\LoggerInterface;

class QueueWorker {

    protected $taskHandler;
    protected $iterations;
    protected $maxIterations;
    protected $logger;

    function __construct(Queue $sourceQueue, Task $taskHandler, $maxIterations = false) {
        $this->sourceQueue    = $sourceQueue;
        $this->taskHandler    = $taskHandler;
        $this->maxIterations  = $maxIterations;
        $this->iterations     = 0;
        $this->logger         = false;
    }

    public function setSourceQueue(Queue $sourceQueue) {
        $this->sourceQueue = $sourceQueue;
    }

    public function setTaskHandler(Task $taskHandler) {
        $this->taskHandler = $taskHandler;
    }

    public function setMaxIterations($maxIterations) {
        $this->maxIterations = $maxIterations;
    }

    public function setLogger(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    public function start() {
        $this->log("debug", "Starting Queue Worker!");
        $this->iterations = 0;
        $this->starting();
        while ($this->isRunning()) {
            $this->iterations++;
            try {
                $task = $this->sourceQueue->getNext();
            } catch (\Exception $exception) {
                $this->log("error", "Error getting data. Message: ". $exception->getMessage());
                $this->sourceQueue->error(FALSE, $exception);
                continue;
            }
            if ($this->isValidTask($task) ) {
                if ($this->taskHandler->mustStop($this->sourceQueue->getMessageBody($task))) {
                    $this->sourceQueue->stopped($task);
                    $this->log("debug", "STOP instruction received.");
                    break;
                }
                $this->manageTask($task);
            } else {
                $this->log("debug", 'Nothing to do.');
                $this->sourceQueue->nothingToDo();
            }
        }
        $this->log("debug", "Queue Worker finished.");
        $this->finished();
    }

    protected function log($type, $message) {
        if($this->logger)
            $this->logger->$type($message);
    }

    protected function starting() {
        return TRUE;
    }

    protected function isRunning() {
        if (is_int($this->maxIterations))
            return ($this->iterations < $this->maxIterations);
        return TRUE;
    }

    protected function isValidTask($task) {
        return ($task !== FALSE);
    }

    private function manageTask($task) {
        try {
            $jobDone = $this->taskHandler->manage($this->sourceQueue->getMessageBody($task));
            if ($jobDone) {
                $this->log("debug", "Successful Job: " . $this->sourceQueue->toString($task));
                $this->sourceQueue->successful($task);
            } else {
                $this->log("debug", "Failed Job:" . $this->sourceQueue->toString($task));
                $this->sourceQueue->failed($task);
            }
        } catch (\Exception $exception) {
            $this->log("error", "Error Managing data. Data :" . $this->sourceQueue->toString($task) . ". Message: " . $exception->getMessage());
            $this->sourceQueue->error($task, $exception);
        }
    }

    protected function finished() {
        return TRUE;
    }

} 