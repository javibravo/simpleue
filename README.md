Simpleue - Simple Queue Worker for PHP
======================================

[![Build Status](https://travis-ci.org/javibravo/simpleue.svg?branch=master)](https://travis-ci.org/javibravo/simpleue)
[![Total Downloads](https://img.shields.io/packagist/dt/javibravo/simpleue.svg)](https://packagist.org/packages/javibravo/simpleue)
[![Latest Stable Version](https://img.shields.io/packagist/v/javibravo/simpleue.svg)](https://packagist.org/packages/javibravo/simpleue)

Simpleue provide a very simple way to run workers to consume queues (consumers) in PHP.
The library have been developed to be easily extended to work with different queue servers and
open to manage any kind of job.

Current implementations:

   - **Redis** queue adapter.
   - **AWS SQS** queue adapter.
   - **Beanstalkd** queue adapter.

You can find an example of use in [simpleue-example](https://github.com/javibravo/simpleue-example)

Worker
------

The lib has a worker class that run and infinite loop (can be stopped with some
conditions) and manage all the stages to process jobs:

   - Get next job.
   - Execute job.
   - job success then do ...
   - job failed then do ...
   - Execution error then do ...
   - No jobs then do ...

The loop can be **stopped** under control using the following methods:

   - **STOP Job** : The job handler allow to define a STOP job.
   - **Max iterations** : It can be specified when the object is declared.

Each worker has one queue source and manage one type of jobs. Many workers
can be working concurrently using the same queue source.

Queue
-----

The lib provide an interface which allow to implement a queue connection for different queue 
servers. Currently the lib provide following implementations:

   - **Redis** queue adapter.
   - **AWS SQS** queue adapter. 
   - **Beanstalkd** queue adapter.

The queue interface manage all related with the queue system and abstract the job about that.

It require the queue system client:

   - Redis : Predis\Client
   - AWS SQS : Aws\Sqs\SqsClient
   - Beanstalkd : Pheanstalk\Pheanstalk;

And was well the source *queue name*. The consumer will need additional queues to manage the process:

   - **Processing queue** (only for Redis): It will store the item popped from source queue while it is being processed.
   - **Failed queue**: All Jobs that fail (according the Job definition) will be add in this queue.
   - **Error queue**: All Jobs that throw and exception in the management process will be add to this queue.

**Important**

For AWS SQS Queue all the queues must exist before start working.

Jobs
----

The job interface is used to manage the job received in the queue. It must manage the domain
business logic and **define the STOP job**.

The job is abstracted form the queue system, so the same job definition is able to work with
different queues interfaces. The job always receive the message body from the queue,

Install
-------

Require the package in your composer json file:

```json
{

    "require": {
        "javibravo/simpleue" : "dev-master",
    },

}
```

Usage
-----

The first step is to define and implement the **Job** to be managed.

```php
<?php

namespace MyProject\MyJob;

use Simpleue\Job\Job;

class MyJob implements Job {

    public function manage($job) {
        ...
        try {
            ...
        } catch ( ... ) {
            return FALSE;
        }
        ...
        return TRUE;
    }

    ...
    
    public function isStopJob($job) {
        if ( ... )
            return TRUE;
        return FALSE;
    }
    
    ...

}
```

Once the job is defined we can define our consumer and start running:

**Redis Consumer**

```php
<?php

use Predis\Client;
use Simpleue\Queue\RedisQueue;
use Simpleue\Worker\QueueWorker;
use MyProject\MyJob;

$redisQueue = new RedisQueue(
    new Client(array('host' => 'localhost', 'port' => 6379, 'schema' => 'tcp')),
    'my_queue_name'
);
$myNewConsumer = new QueueWorker($redisQueue, new MyJob());
$myNewConsumer->start();
```

**AWS SQS Consumer**

```php
<?php

use Aws\Sqs\SqsClient;
use Simpleue\Queue\SqsQueue;
use Simpleue\Worker\QueueWorker;
use MyProject\MyJob;

$sqsClient = new SqsClient([
    'profile' => 'aws-profile',
    'region' => 'eu-west-1',
    'version' => 'latest'
]);

$sqsQueue = new SqsQueue($sqsClient, 'my_queue_name');

$myNewConsumer = new QueueWorker($sqsQueue, new MyJob());
$myNewConsumer->start();
```

**Beanstalkd Consumer**

```php
<?php

use Simpleue\Queue\BeanStalkdQueue;
use Simpleue\Worker\QueueWorker;
use Pheanstalk\Pheanstalk;
use MyProject\MyJob;

$beanStalkdClient = new Pheanstalk('localhost');

$beanStalkdQueue = new BeanStalkdQueue($beanStalkdClient, 'my_queue_name');

$myNewConsumer = new QueueWorker($beanStalkdQueue, new MyJob());
$myNewConsumer->start();
```

(*) The idea is to support any queue system, so it is open for that. Contributions are welcome.
