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
        $this->results[\spl_object_hash($entity)] = $result;
    }

    /**
     * Search Result
     *
     * @param object $entity
     * @return \Tnw\SoapClient\Result\UpsertResult
     */
    public function searchResult($entity)
    {
        $hash = \spl_object_hash($entity);
        if (empty($this->results[$hash])) {
            return null;
        }

        return $this->results[$hash];
    }
}
