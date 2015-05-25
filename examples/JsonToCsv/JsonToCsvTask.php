<?php
/**
 * Created by PhpStorm.
 * User: jbravo
 * Date: 25/05/15
 * Time: 11:39
 */

namespace Examples\JsonToCsv;

use SimplePhpQueue\Task\Task;

class JsonToCsvTask implements  Task {

    public function manage($task) {
        $jsonData = $this->validateJson($task);
        if (is_array($jsonData)) {
            $flattenedData = $this->toFlattenedArray($jsonData);
            echo implode(',', $flattenedData)."\n";
            return TRUE;
        }
        return FALSE;
    }

    protected function toFlattenedArray(array $array) {
        $flattenedArray = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $flattenedArray = array_merge($flattenedArray, $this->toFlattenedArray($value));
            } else {
                if (!array_key_exists($key, $flattenedArray))
                    $flattenedArray[$key] = $value;
            }
        }
        return $flattenedArray;
    }

    protected function validateJson($string) {
        $array = json_decode($string, TRUE);
        if (json_last_error() != JSON_ERROR_NONE)
            return FALSE;
        return $array;
    }

}