<?php
namespace TNW\Salesforce\Synchronize\Transport\Soap\Calls\Upsert;

use TNW\Salesforce\Synchronize\Transport;

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
     * @param \Tnw\SoapClient\Result\UpsertResult $result
     */
    public function saveResult($entity, $result)
    {
        // hack, actually different objects could have the same hash
        $classType = get_class($entity);
        $this->results[\spl_object_hash($entity->getData('_queue')) . $classType] = $result;
    }

    /**
     * Search Result
     *
     * @param object $entity
     * @return \Tnw\SoapClient\Result\UpsertResult
     */
    public function searchResult($entity)
    {
        // hack, actually different objects could have the same hash
        $classType = get_class($entity);
        $hash = \spl_object_hash($entity->getData('_queue')) . $classType;
        if (empty($this->results[$hash])) {
            return null;
        }

        return $this->results[$hash];
    }
}
