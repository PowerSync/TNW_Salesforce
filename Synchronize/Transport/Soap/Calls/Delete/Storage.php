<?php

namespace TNW\Salesforce\Synchronize\Transport\Soap\Calls\Delete;

use TNW\Salesforce\Synchronize\Transport;
use Tnw\SoapClient\Result\DeleteResult;
use function spl_object_hash;

/**
 * Soap Upsert Storage
 */
class Storage
{
    /**
     * @var array
     */
    private $results = [];

    /**
     * Save Result
     *
     * @param object $entity
     * @param DeleteResult $result
     */
    public function saveResult($entity, $result)
    {
        // hack, actually different objects could have the same hash
        $classType = get_class($entity);
        $this->results[spl_object_hash($entity) . $classType] = $result;
    }

    /**
     * Search Result
     *
     * @param object $entity
     * @return DeleteResult
     */
    public function searchResult($entity)
    {
        // hack, actually different objects could have the same hash
        $classType = get_class($entity);
        $hash = spl_object_hash($entity) . $classType;
        if (empty($this->results[$hash])) {
            return null;
        }

        return $this->results[$hash];
    }
}
