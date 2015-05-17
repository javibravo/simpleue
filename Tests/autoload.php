<?php
/**
 * Created By: Javier Bravo
 * Date: 9/01/15
 */

require_once dirname(__FILE__).'/../src/autoload.php';

function testsAutoload($class) {
    $filePath = dirname(__FILE__) . '/../' . str_replace('\\', '/', $class).'.php';
    if (file_exists($filePath)) {
        require_once $filePath;
    }
}
spl_autoload_register('testsAutoload');
