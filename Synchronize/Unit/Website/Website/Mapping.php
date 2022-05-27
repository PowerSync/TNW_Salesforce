<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Synchronize\Unit\Website\Website;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\Website;
use TNW\Salesforce\Model;
use TNW\Salesforce\Synchronize;

/**
 * Mapping
 */
class Mapping extends Synchronize\Unit\Mapping
{
    /**
     * Object By Entity Type
     *
     * @param Website $entity
     * @param string $magentoEntityType
     * @return mixed
     * @throws LocalizedException
     */
    public function objectByEntityType($entity, $magentoEntityType)
    {
        if ($magentoEntityType === 'website') {
            return $entity;
        }

        return parent::objectByEntityType($entity, $magentoEntityType);
    }

    /**
     * Prepare Value
     *
     * @param Website $entity
     * @param string $attributeCode
     * @return mixed
     */
    public function prepareValue($entity, $attributeCode)
    {
        $attributeCode = (string)$attributeCode;
        if ($entity instanceof Website && strcasecmp($attributeCode, 'sforce_id') === 0) {
            return $this->lookup()->get('%s/record/Id', $entity);
        }

        return parent::prepareValue($entity, $attributeCode);
    }

    /**
     * Mappers
     *
     * @param Website $entity
     * @return Model\ResourceModel\Mapper\Collection
     * @throws Exception
     */
    public function mappers($entity)
    {
        $collection = parent::mappers($entity);

        /** @var Model\Mapper $mapper */
        $mapper = $collection->getNewEmptyItem()->addData([
            'magento_attribute_name' => 'sforce_id',
            'salesforce_attribute_name' => 'Id',
            'magento_entity_type' => 'website',
            'default_value' => null,
        ]);
        $collection->addItem($mapper);

        /** @var Model\Mapper $mapper */
        $mapper = $collection->getNewEmptyItem()->addData([
            'magento_attribute_name' => 'name',
            'salesforce_attribute_name' => 'Name',
            'magento_entity_type' => 'website',
            'default_value' => null,
        ]);
        $collection->addItem($mapper);

        /** @var Model\Mapper $mapper */
        $mapper = $collection->getNewEmptyItem()->addData([
            'magento_attribute_name' => 'website_id',
            'salesforce_attribute_name' => 'tnw_mage_basic__Website_ID__c',
            'magento_entity_type' => 'website',
            'default_value' => null,
        ]);
        $collection->addItem($mapper);

        /** @var Model\Mapper $mapper */
        $mapper = $collection->getNewEmptyItem()->addData([
            'magento_attribute_name' => 'code',
            'salesforce_attribute_name' => 'tnw_mage_basic__Code__c',
            'magento_entity_type' => 'website',
            'default_value' => null,
        ]);
        $collection->addItem($mapper);

        /** @var Model\Mapper $mapper */
        $mapper = $collection->getNewEmptyItem()->addData([
            'magento_attribute_name' => 'sort_order',
            'salesforce_attribute_name' => 'tnw_mage_basic__Sort_Order__c',
            'magento_entity_type' => 'website',
            'default_value' => null,
        ]);
        $collection->addItem($mapper);

        return $collection;
    }
}
