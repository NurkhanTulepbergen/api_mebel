<?php
namespace App\Http\Traits;

trait ChangeFieldsTrait
{
    function changeSingleCollection($collection, array $mapping) {
        $mappedItem = [];
        foreach ($mapping as $dbField => $apiField) {
            if(isset($collection->$dbField)) $mappedItem[$apiField] = $collection->$dbField;
        }
        return $mappedItem;
    }
}
