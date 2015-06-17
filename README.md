SimplePHPQueue
==============

SimplePHPQueue allows to run workers to consume queues in a very simple way. The
library have been developed to be easily extended for any(*) queue server and
open to manage any kind of task.

Currently the lib only implement Redis queue interface, but open to implement any
other.

Worker
------

The lib has a worker class that run and infinite loop (can be stopped with some
conditions) that manage all the stages to process tasks:

   - Get next task.
   - Execute task.
   - Task success then do ...
   - Task failed then do ...
   - Execution error then do ...
   - No tasks then do ...

The loop can be stopped specifying a maximum of iterations or with the special
command "STOP" sent as a task.

Each running worker has one queue source and manage one type of tasks. Many workers
can be working concurrently using the same queue source.

Queue
-----

The lib provide an interface which allow to implement a queue connection for
any(*) queue system. Currently Redis queue interface is the only one implemented.

Task
----

The task interface is used to manage the task received in the queue.

Install
-------

Require the package in your composer json file:

```json
{
    ...

    "require": {
        ...
        "javibravo/simple-php-queue" : "dev-master",
        ...
    },

   ...
}
```

Usage
-----

The firs step is to define and implement the task to be managed.

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
        return TRUE;
    }

    ...

}
```

Once the task is defined we can define our worker and start running:

```php
<?php

use Predis\Client;
use SimplePhpQueue\Queue\RedisQueue;
use SimplePhpQueue\Worker\QueueWorker;
use SimplePhpQueue\Worker\QueueWorker;
use MyProject\MyTask;

$redisQueue = new RedisQueue(
    new Client(array('host' => 'localhost', 'port' => 6379, 'schema' => 'tcp')),
    'queue.json.csv'
);
$jsonToCsvWorker = new QueueWorker($redisQueue, new MyTask());
$jsonToCsvWorker->start();
```


(*) Currently it is only working with redis queues. The idea is to support any queue
system, so it is open for that but not checked or tested with any other.