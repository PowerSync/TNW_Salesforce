<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Unit;

use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use TNW\Salesforce\Model\Entity\SalesforceIdStorage;

/**
 * Mapping Entity Loader
 */
abstract class EntityLoaderAbstract
{
    /**
     * @var SalesforceIdStorage
     */
    protected $salesforceIdStorage;

    /**
     * MappingEntityLoaderAbstract constructor.
     * @param SalesforceIdStorage $salesforceIdStorage
     */
    public function __construct(
        SalesforceIdStorage $salesforceIdStorage = null
    ) {
        $this->salesforceIdStorage = $salesforceIdStorage;
    }

    /**
     * Load
     *
     * @param AbstractModel $entity
     * @return DataObject|null
     * @throws LocalizedException
     */
    public function get($entity): ?DataObject
    {
        $subEntity = $this->load($entity);
        if (!empty($subEntity) && null !== $this->salesforceIdStorage && null !== $subEntity->getId()) {
            $this->salesforceIdStorage->load($subEntity, $entity->getData('config_website'));
        }

        return $subEntity;
    }

    /**
     * @return SalesforceIdStorage|null
     */
    public function getSalesforceIdStorage(): ?SalesforceIdStorage
    {
        return $this->salesforceIdStorage;
    }

    /**
     * Load
     *
     * @param AbstractModel $entity
     * @return DataObject|ExtensibleDataInterface|null
     */
    abstract public function load($entity);
}
