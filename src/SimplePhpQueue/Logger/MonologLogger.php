<?php
/**
 * Created By: Javier Bravo
 * Date: 7/01/15
 */

namespace SimplePhpQueue\Logger;

use Monolog\Logger as MonologLog;
use Monolog\Handler\StreamHandler;

class MonologLogger implements Logger {

    private $monolog;

    public function __construct (MonologLog $logger) {
        $this->monolog = $logger;
    }

    public function info($message) {
        $this->monolog->info($message);
    }

    public function debug($message) {
        $this->monolog->debug($message);
    }

    public function warning($message) {
        $this->monolog->warning($message);
    }

    public function error($message) {
        $this->monolog->error($message);
    }

    public static function getInstance($name, $logFilePath, $logLevel = MonologLog::WARNING) {
        $monologLogger = new MonologLog($name);
        $monologLogger->pushHandler(new StreamHandler($logFilePath, $logLevel));

        $logger = new MonologLogger($monologLogger);
        return $logger;
    }

} 