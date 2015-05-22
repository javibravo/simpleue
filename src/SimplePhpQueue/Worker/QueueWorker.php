<?php
/**
 * User: Javier Bravo
 * Date: 9/01/15
 */

namespace SimplePhpQueue\Worker;

use SimplePhpQueue\Queue\SourceQueue;
use SimplePhpQueue\Handler\TaskHandler;
use SimplePhpQueue\Logger\LoggerConfig;
use SimplePhpQueue\Logger\Logger;

class QueueWorker {

    const STOP_INSTRUCTION = "STOP";

    protected $taskHandler;
    protected $iterations;
    protected $maxIterations;
    protected $logger;

    function __construct(SourceQueue $sourceQueue, TaskHandler $taskHandler, $maxIterations = false) {
        $this->sourceQueue    = $sourceQueue;
        $this->taskHandler    = $taskHandler;
        $this->maxIterations  = $maxIterations;
        $this->iterations     = 0;
//        $this->logger         = LoggerConfig::getlogger(get_class($this), false);
        $this->logger         = false;
    }

    public function setSourceQueue(SourceQueue $sourceQueue) {
        $this->sourceQueue = $sourceQueue;
    }

    public function setTaskHandler(TaskHandler $taskHandler) {
        $this->taskHandler = $taskHandler;
    }

    public function setMaxIterations($maxIterations) {
        $this->maxIterations = $maxIterations;
    }

    public function setLogger(Logger $logger) {
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
                $this->sourceQueue->error(false, $exception);
                continue;
            }
            if ($task !== false ) {
                if ($task === self::STOP_INSTRUCTION) {
                    $this->log("debug", "STOP instruction received.");
                    break;
                }
                try {
                    $jobDone = $this->taskHandler->manage($task);
                    if ($jobDone) {
                        $this->log("debug", "Successful Job: " . $task);
                        $this->sourceQueue->successful($task);
                    } else {
                        $this->log("debug", "Failed Job:" . $task);
                        $this->sourceQueue->failed($task);
                    }
                } catch (\Exception $exception) {
                    $this->log("error", "Error Managing data. Data :" . $task .". Message: ". $exception->getMessage());
                    $this->sourceQueue->error($task, $exception);
                }
            } else {
                $this->log("debug", 'Nothing to do.');
                $this->sourceQueue->nothingToDo();
            }
        }
        $this->log("debug", "Queue Worker finished.");
        $this->finished();
    }

    protected function starting() {
        return TRUE;
    }

    protected function isRunning() {
        if (is_int($this->maxIterations))
            return ($this->iterations < $this->maxIterations);
        return TRUE;
    }

    protected function finished() {
        return TRUE;
    }

    protected function log($type, $message) {
        if($this->logger)
            $this->logger->$type($message);
    }

} 