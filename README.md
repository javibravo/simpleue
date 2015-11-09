SimplePHPQueue
==============

[![Build Status](https://travis-ci.org/javibravo/simple-php-queue.svg?branch=master)](https://travis-ci.org/javibravo/simple-php-queue)

SimplePHPQueue provide a very simple way to run workers to consume queues (consumers).
The library have been developed to be easily extended to work with different queue servers and
open to manage any kind of tasks.

Current implementations:

   - Redis queue interface.
   - AWS SQS queue interface. 

You can find an example of use in [simple-php-queue-example](https://github.com/javibravo/simple-php-queue-example)

Worker
------

The lib has a worker class that run and infinite loop (can be stopped with some
conditions) and manage all the stages to process tasks:

   - Get next task.
   - Execute task.
   - Task success then do ...
   - Task failed then do ...
   - Execution error then do ...
   - No tasks then do ...

The loop can be stopped specifying a maximum of iterations or with and STOP task that must 
be defined and managed by the Task Handler.

Each worker has one queue source and manage one type of tasks. Many workers
can be working concurrently using the same queue source.

Queue
-----

The lib provide an interface which allow to implement a queue connection for different queue 
servers. Currently the lib provide following implementations:

   - Redis queue interface.
   - AWS SQS queue interface. 

The queue interface manage all related with the queue system and abstract the task about that.

It require the queue system client:

   - Redis : Predis\Client
   - AWS SQS : Aws\Sqs\SqsClient

And was well the source *queue name*. The consumer will need additional queues to manage the process:

   - **Processing queue** (only for Redis): It will store the item popped from source queue while it is being processed.
   - **Failed queue**: All tasks that fail (according the Task definition) will be add in this queue.
   - **Error queue**: All tasks that throw and exception in the management process will be add to this queue.

**Important**

For AWS SQS Queue all the queues must exist before start working.

Task
----

The task interface is used to manage the task received in the queue. It must manage the domain
business logic and define the STOP task.

The task is abstracted form the queue system, so the same task definition is able to work with 
different queues interfaces. The task always receive the message body from the queue,

Install
-------

Require the package in your composer json file:

```json
{

    "require": {
        "javibravo/simple-php-queue" : "dev-master",
    },

}
```

Usage
-----

The first step is to define and implement the task to be managed.

```php
<?php

namespace MyProject\MyTask;

use SimplePhpQueue\Task\Task;

class MyTask implements  Task {

    public function manage($task) {
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
    
    public function mustStop($task) {
        if ( ... )
            return TRUE;
        return FALSE;
    }
    
    ...

}
```

Once the task is defined we can define our consumer and start running:

**Redis Consumer**

```php
<?php

use Predis\Client;
use SimplePhpQueue\Queue\RedisQueue;
use SimplePhpQueue\Worker\QueueWorker;
use MyProject\MyTask;

$redisQueue = new RedisQueue(
    new Client(array('host' => 'localhost', 'port' => 6379, 'schema' => 'tcp')),
    'my_queue_name'
);
$myNewConsumer = new QueueWorker($redisQueue, new MyTask());
$myNewConsumer->start();
```

**AWS SQS Consumer**

```php
<?php

use Aws\Sqs\SqsClient;
use SimplePhpQueue\Queue\AwsSqsQueue;
use SimplePhpQueue\Worker\QueueWorker;
use MyProject\MyTask;

$sqsClient = new SqsClient([
    'profile' => 'aws-profile',
    'region' => 'eu-west-1',
    'version' => 'latest'
]);

$sqsQueue = new AwsSqsQueue($sqsClient, 'my_queue_name');

$myNewConsumer = new QueueWorker($sqsQueue, new MyTask());
$myNewConsumer->start();
```

(*) The idea is to support any queue system, so it is open for that. Contributions are welcome.