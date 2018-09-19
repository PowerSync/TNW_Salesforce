<?php
namespace TNW\Salesforce\Synchronize\Unit\Website\Website;

use TNW\Salesforce\Synchronize;
use TNW\Salesforce\Model;

class Mapping extends Synchronize\Unit\MappingAbstract
{

    /**
     * @param \Magento\Framework\Model\AbstractModel $entity
     * @param string $magentoEntityType
     * @return mixed
     */
    protected function objectByEntityType($entity, $magentoEntityType)
    {
        switch ($magentoEntityType) {
            case 'website':
                return $entity;

            default:
                return null;
        }
    }

    public function prepareValue($entity, $attributeCode)
    {
        if ($entity instanceof \Magento\Store\Model\Website && strcasecmp($attributeCode, 'sforce_id') === 0) {
            return $this->unit('websiteWebsiteLookup')->get('%s/record/Id', $entity);
        }

        return parent::prepareValue($entity, $attributeCode);
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $entity
     * @return Model\ResourceModel\Mapper\Collection
     * @throws \Exception
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