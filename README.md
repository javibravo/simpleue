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

Usage
-----



(*) Currently it is only working with redis queues. The idea is to support any queue
system, so it is open for that but not checked or tested with any other.