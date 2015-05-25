<?php
/**
 * Created by PhpStorm.
 * User: jbravo
 * Date: 25/05/15
 * Time: 12:18
 */

namespace Examples\JsonToCsv;

require_once dirname(__FILE__).'/../autoload.php';

use Predis\Client;
use SimplePhpQueue\Queue\RedisQueue;
use SimplePhpQueue\Worker\QueueWorker;
use SimplePhpQueue\Logger\Log4PhpLogger;

date_default_timezone_set('Europe/London');

$redisQueue = new RedisQueue(
    new Client(array('host' => 'localhost', 'port' => 6379, 'schema' => 'tcp')),
    'queue.json.csv'
);
$jsonToCsvWorker = new QueueWorker($redisQueue, new JsonToCsvTask());
$jsonToCsvWorker->setLogger(Log4PhpLogger::getInstance(dirname(__FILE__).'/../../logs', __FILE__));
$jsonToCsvWorker->start();