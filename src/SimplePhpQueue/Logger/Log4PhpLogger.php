<?php
/**
 * Created By: Javier Bravo
 * Date: 7/01/15
 */

namespace SimplePhpQueue\Logger;

require_once dirname(__FILE__) . '/../../autoload.php';

use LoggerAppenderFile;
use LoggerLayoutPattern;
use \Logger as Log4Php;

class Log4PhpLogger implements Logger {

    const   CLASS_PATH_PATTERN = '/((^.*)\\\)*(.*)/';
    static  $firstCall         = TRUE;
    private $log4php;

    public function __construct (Log4Php $logger) {
        $this->log4php = $logger;
    }

    public function info($message) {
        $this->log4php->info($message);
    }

    public function debug($message) {
        $this->log4php->debug($message);
    }

    public function warn($message) {
        $this->log4php->warn($message);
    }

    public function error($message) {
        $this->log4php->error($message);
    }

    public function fatal($message) {
        $this->log4php->fatal($message);
    }

    public static function getInstance($logRootDirectory, $fullClassName = false) {
        if (!$fullClassName)
            $fullClassName = get_called_class();
        if (self::$firstCall) {
            Log4Php::configure(array('appenders' => array()));
            self::$firstCall = FALSE;
        }
        list ($nameSpace, $className) = self::getNameSpaceAndClassName($fullClassName);
        $previouslyExisted = Log4Php::exists($className);
        $logger = Log4Php::getLogger($className);
        if (!$previouslyExisted) {
            $layout = new LoggerLayoutPattern();
            $layout->setConversionPattern('%date [%logger] %-5level : %message%newline');
            $layout->activateOptions();

            $appender = new LoggerAppenderFile($className);
            $logRootDirectory = rtrim($logRootDirectory, '/');
            $appender->setFile("$logRootDirectory/$nameSpace/$className.log");
            $appender->setAppend(TRUE);
            $appender->setThreshold('all');
            $appender->setLayout($layout);
            $appender->activateOptions();
            $logger->addAppender($appender);
        }

        return new Log4PhpLogger($logger);
    }

    private static function getNameSpaceAndClassName($fullClassName) {
        preg_match(self::CLASS_PATH_PATTERN, $fullClassName, $matches, PREG_OFFSET_CAPTURE);
        $className = $matches[3][0];
        $nameSpace = $matches[2][0];
        $nameSpace = str_replace('\\', '/', $nameSpace);
        return [$nameSpace, $className];
    }

} 