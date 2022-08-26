<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Synchronize\Unit\Loader;

/**
 * Mapping Entity Loader
 */
abstract class EntityAbstract implements \TNW\Salesforce\Synchronize\Unit\LoadLoaderInterface
{
    /**
     * @var \TNW\Salesforce\Model\Entity\SalesforceIdStorage
     */
    private $salesforceIdStorage;

    /**
     * MappingEntityLoaderAbstract constructor.
     * @param \TNW\Salesforce\Model\Entity\SalesforceIdStorage $salesforceIdStorage
     */
    public function __construct(
        \TNW\Salesforce\Model\Entity\SalesforceIdStorage $salesforceIdStorage = null
    ) {
        $this->salesforceIdStorage = $salesforceIdStorage;
    }

    /**
     * loadSalesforceId
     *
     * @param \Magento\Framework\Model\AbstractModel $entity
     * @return \Magento\Framework\Model\AbstractModel
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function loadSalesforceId($entity)
    {
        if (null !== $this->salesforceIdStorage && null !== $entity->getId()) {
            $this->salesforceIdStorage->load($entity, $entity->getData('config_website'));
        }

        return $entity;
    }
}
