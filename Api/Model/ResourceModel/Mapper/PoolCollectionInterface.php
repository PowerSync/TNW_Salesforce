<?php
declare(strict_types=1);

namespace TNW\Salesforce\Api\Model\ResourceModel\Mapper;

use Magento\Framework\DataObject;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Interface PoolCollectionInterface
 * @package TNW\Salesforce\Api\Model\ResourceModel\Mapper
 */
interface PoolCollectionInterface
{
    /**
     * @return mixed
     */
    public function getPoolCollection(DataObject $entity, $context = null):?AbstractCollection;

    /**
     * @param $entity
     * @param $collection
     * @return mixed
     */
    public function setCollectionToPool(DataObject $entity, AbstractCollection $collection): void;

    /**
     * @return string
     */
    public function getHash(DataObject $entity): string;
}
