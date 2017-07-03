<?php
/**
 * User: Javier Bravo
 * Date: 9/01/15.
 */
namespace Simpleue\Worker;

use Simpleue\Queue\Queue;
use Simpleue\Job\Job;
use Psr\Log\LoggerInterface;

class QueueWorker
{
    protected $queueHandler;
    protected $jobHandler;
    protected $iterations;
    protected $maxIterations;
    protected $logger;
    protected $terminated;

    public function __construct(Queue $queueHandler, Job $jobHandler, $maxIterations = 0, $handleSignals = false)
    {
        $this->queueHandler = $queueHandler;
        $this->jobHandler = $jobHandler;
        $this->maxIterations = (int) $maxIterations;
        $this->iterations = 0;
        $this->logger = false;
        $this->terminated = false;

        if ($handleSignals) {
            $this->handleSignals();
        }
    }

    public function setQueueHandler(Queue $queueHandler)
    {
        $this->queueHandler = $queueHandler;

        return $this;
    }

    public function setJobHandler(Job $jobHandler)
    {
        $this->jobHandler = $jobHandler;

        return $this;
    }

    public function setMaxIterations($maxIterations)
    {
        $this->maxIterations = (int) $maxIterations;

        return $this;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    public function start()
    {
        $this->log('debug', 'Starting Queue Worker!');
        $this->iterations = 0;
        $this->starting();
        while ($this->isRunning()) {
            ++$this->iterations;
            try {
                $job = $this->queueHandler->getNext();
            } catch (\Exception $exception) {
                $this->log('error', 'Error getting data. Message: '.$exception->getMessage());
                $this->queueHandler->error(false, $exception);
                continue;
            }
            if ($this->isValidJob($job)) {
                if ($this->jobHandler->isStopJob($this->queueHandler->getMessageBody($job))) {
                    $this->queueHandler->stopped($job);
                    $this->log('debug', 'STOP instruction received.');
                    break;
                }
                $this->manageJob($job);
            } else {
                $this->log('debug', 'Nothing to do.');
                $this->queueHandler->nothingToDo();
            }
        }
        $this->log('debug', 'Queue Worker finished.');
        $this->finished();
    }

    protected function log($type, $message)
    {
        if ($this->logger) {
            $this->logger->$type($message);
        }
    }

    protected function starting()
    {
        return true;
    }

    protected function isRunning()
    {
        if ($this->terminated) {
            return false;
        }

        if ($this->maxIterations > 0) {
            return $this->iterations < $this->maxIterations;
        }

        return true;
    }

    protected function isValidJob($job)
    {
        return $job !== false;
    }

    protected function handleSignals()
    {
        if (!function_exists('pcntl_signal')) {
            $this->log(
                'error',
                'Please make sure that \'pcntl\' is enabled if you want us to handle signals'
            );

            throw new \Exception('Please make sure that \'pcntl\' is enabled if you want us to handle signals');
        }

        declare(ticks = 1);
        pcntl_signal(SIGTERM, [$this, 'terminate']);
        pcntl_signal(SIGINT,  [$this, 'terminate']);

        $this->log('debug', 'Finished Setting up Handler for signals SIGTERM and SIGINT');
    }

    protected function terminate()
    {
        $this->log('debug', 'Caught signals. Trying a Graceful Exit');
        $this->terminated = true;
    }

    private function manageJob($job)
    {
        try {
            $jobDone = $this->jobHandler->manage($this->queueHandler->getMessageBody($job));
            if ($jobDone) {
                $this->log('debug', 'Successful Job: '.$this->queueHandler->toString($job));
                $this->queueHandler->successful($job);
            } else {
                $this->log('debug', 'Failed Job:'.$this->queueHandler->toString($job));
                $this->queueHandler->failed($job);
            }
        } catch (\Exception $exception) {
            $this->log('error', 'Error Managing data. Data :'.$this->queueHandler->toString($job).'. Message: '.$exception->getMessage());
            $this->queueHandler->error($job, $exception);
        }
    }

    protected function finished()
    {
        return true;
    }
}
