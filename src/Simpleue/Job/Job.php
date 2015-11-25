<?php
/**
 * User: Javier Bravo
 * Date: 10/05/15
 */

namespace Simpleue\Job;


interface Job {
    public function manage($job);
    public function mustStop($job);
}