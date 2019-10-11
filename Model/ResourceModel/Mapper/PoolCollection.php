<?php
declare(strict_types=1);

namespace  TNW\Salesforce\Model\ResourceModel\Mapper;

use Magento\Framework\DataObject;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use TNW\Salesforce\Api\Model\ResourceModel\Mapper\PoolCollectionInterface;

/**
 * Class PoolCollection
 * @package TNW\Salesforce\Model\ResourceModel\Mapper
 */
class PoolCollection implements PoolCollectionInterface
{
    protected $context = null;
    /**
     * @var array
     */
    private $poolCollection = [];

    /**
     * @param DataObject $entity
     * @param null $context
     * @return mixed|null
     */
    public function getPoolCollection(DataObject $entity, $context = null): ?AbstractCollection
    {
        $this->context = $context;
        $hash = $this->getHash($entity);
        if (isset($this->poolCollection[$hash])) {
            return $this->poolCollection[$hash];
        }
        return null;
    }

    /**
     * @param $entity
     * @return mixed
     */
    public function getHash(DataObject $entity): string
    {
        $objectType = $entity->getData('_queue')->getObjectType();
        $websiteId = $entity->getWebsiteId();
        $strFormat = "%s_%s";
        $key = sprintf($strFormat, $websiteId, $objectType);
        return sha1($key);
    }

    /**
     * @param DataObject $entity
     * @param $collection
     */
    public function setCollectionToPool(DataObject $entity, AbstractCollection $collection): void
    {
        $hash = $this->getHash($entity);
        $this->poolCollection[$hash] = $collection;
    }
}
