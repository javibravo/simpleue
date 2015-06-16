<?php
/**
 * Created by PhpStorm.
 * User: jbravo
 * Date: 10/05/15
 * Time: 18:32
 */

namespace SimplePhpQueue\Mocks;

use Psr\Log\LoggerInterface;

class LoggerSpy implements LoggerInterface {

    public $errorMessages;
    public $debugMessages;

    public function _construct() {
        $this->errorMessages = array();
        $this->debugMessages = array();
    }

    public function error($message, array $context = array()) {
        $this->errorMessages[] = $message;
    }

    public function debug($message, array $context = array()) {
        $this->debugMessages[] = $message;
    }

    public function emergency($message, array $context = array()) {}
    public function alert($message, array $context = array()) {}
    public function critical($message, array $context = array()) {}
    public function warning($message, array $context = array()) {}
    public function notice($message, array $context = array()) {}
    public function info($message, array $context = array()) {}
    public function log($level, $message, array $context = array()) {}

}