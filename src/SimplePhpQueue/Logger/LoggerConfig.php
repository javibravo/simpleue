<?php
/**
 * Created By: Javier Bravo
 * Date: 7/01/15
 */

namespace SimplePhpQueue\Logger;

require_once dirname(__FILE__) . '/../../autoload.php';

use LoggerAppenderFile;
use LoggerLayoutPattern;
use Symfony\Component\Yaml;

class LoggerConfig
{
    const CLASS_PATH_PATTERN = '/((^.*)\\\)*(.*)/';

    static $flagFirstTime = TRUE;

    public static function getLogger($fullClassName)
    {
        if (self::$flagFirstTime) {
            \Logger::configure(
                array(
                    'appenders' => array()
                )
            );
            self::$flagFirstTime = FALSE;
        }
        preg_match(self::CLASS_PATH_PATTERN, $fullClassName, $matches, PREG_OFFSET_CAPTURE);
        $className = $matches[3][0];

        $exists = \Logger::exists($className);
        if ($exists) {
            $logger = \Logger::getLogger($className);
        } else {
            $logger = \Logger::getLogger($className);

            $layout = new LoggerLayoutPattern();
            $layout->setConversionPattern('%date [%logger] %-5level : %message%newline');
            $layout->activateOptions();

            $namespace = $matches[2][0];
            $namespace = str_replace('\\', '/', $namespace);

            $appender = new LoggerAppenderFile($className);
            $appender->setFile(dirname(__FILE__)."/../../../logs/" . $namespace . '/' . $className . '.log');
            $appender->setAppend(TRUE);
            $appender->setThreshold('all');

            $appender->setLayout($layout);
            $appender->activateOptions();
            $logger->addAppender($appender);
        }

        return $logger;
    }

} 