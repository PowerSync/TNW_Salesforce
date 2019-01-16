<?php
namespace TNW\Salesforce\Synchronize\Transport\Soap\Calls\Upsert;

use TNW\Salesforce\Synchronize\Transport;

class Storage
{
    /**
     * @var array
     */
    private $results = [];

    /**
     * @var Transport\Soap\ClientFactory
     */
    private $factory;

    public function __construct(
        Transport\Soap\ClientFactory $factory
    ) {
        $this->factory = $factory;
    }

    /**
     * @param object $entity
     * @param \Tnw\SoapClient\Result\UpsertResult $result
     */
    private function saveResult($entity, $result)
    {
        $this->results[\spl_object_hash($entity)] = $result;
    }

    /**
     * @param array $batch
     * @param array $entities
     * @param string $externalIdFieldName
     * @param string $type
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setBatchByEntities($batch, $entities, $externalIdFieldName, $type)
    {
        $results = $this->factory->client()->upsert($externalIdFieldName, $batch, $type);
        foreach ($entities as $key => $entity) {
            if (empty($results[$key])) {
                continue;
            }

            $this->saveResult($entity, $results[$key]);
        }
    }

    /**
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

    /**
     * Clear
     */
    public function clear()
    {
        $this->results = [];
    }
}
