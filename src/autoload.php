<?php
/**
 * Created By: Javier Bravo
 * Date: 7/01/15
 */

require_once dirname(__FILE__).'/../vendor/autoload.php';

function queueWorkerAutoload($clase) {
    $filePath = dirname(__FILE__) . '/' . str_replace('\\', '/', $clase).'.php';
    if (file_exists($filePath)) {
        require_once $filePath;
    }
}
spl_autoload_register('queueWorkerAutoload');

