<?php
namespace TNW\Salesforce\Synchronize\Unit;

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
     * @return AbstractModel
     * @throws LocalizedException
     */
    public function get($entity)
    {
        $subEntity = $this->load($entity);
        if (!empty($subEntity) && null !== $this->salesforceIdStorage && null !== $subEntity->getId()) {
            $this->salesforceIdStorage->load($subEntity, $entity->getData('config_website'));
        }

        return $subEntity;
    }

    /**
     * Load
     *
     * @param AbstractModel $entity
     * @return AbstractModel
     */
    abstract public function load($entity);
}
