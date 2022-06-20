<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use TNW\Salesforce\Model\MapperFactory;
use TNW\Salesforce\Model\ResourceModel\Mapper;

/**
 *  Create Order invoice mapping
 */
class AddOrderInvoiceMapping implements DataPatchInterface
{
    /** @var ModuleDataSetupInterface */
    private $moduleDataSetup;

    /** @var Mapper */
    private $resource;

    /** @var MapperFactory */
    private $modelFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        Mapper $resource,
        MapperFactory $modelFactory
    )
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->resource = $resource;
        $this->modelFactory = $modelFactory;
    }

    /**
     * @inheriDoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheriDoc
     */
    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $map = [
            [
                'object_type' => 'tnw_invoice__Invoice__c',
                'magento_entity_type' => 'invoice',
                'magento_attribute_name' => 'grand_total',
                'salesforce_attribute_name' => 'tnw_invoice__Grand_Total__c',
                'is_default' => 1,
            ],
            [
                'object_type' => 'tnw_invoice__Invoice__c',
                'magento_entity_type' => 'invoice',
                'magento_attribute_name' => 'tax_amount',
                'salesforce_attribute_name' => 'tnw_invoice__Tax__c',
                'is_default' => 1,
            ],
            [
                'object_type' => 'tnw_invoice__Invoice__c',
                'magento_entity_type' => 'invoice',
                'magento_attribute_name' => 'discount_amount',
                'salesforce_attribute_name' => 'tnw_invoice__Discount__c',
                'is_default' => 1,
            ],
            [
                'object_type' => 'tnw_invoice__Invoice__c',
                'magento_entity_type' => 'invoice',
                'magento_attribute_name' => 'subtotal',
                'salesforce_attribute_name' => 'tnw_invoice__Subtotal__c',
                'is_default' => 1,
            ],
            [
                'object_type' => 'tnw_invoice__Invoice__c',
                'magento_entity_type' => 'invoice',
                'magento_attribute_name' => 'shipping_amount',
                'salesforce_attribute_name' => 'tnw_invoice__Shipping__c',
                'is_default' => 1,
            ],
        ];
        
        foreach ($map as $itemData) {
            /** @var \TNW\Salesforce\Model\Mapper $object */
            $object = $this->modelFactory->create();
            $objectType = $itemData['object_type'];
            $magentoEntityType = $itemData['magento_entity_type'];
            $magentoAttributeName = $itemData['magento_attribute_name'];
            $salesForceAttributeName = $itemData['salesforce_attribute_name'];
            $this->resource->loadByUniqueFields(
                $object, 
                $objectType,
                $magentoEntityType,
                $magentoAttributeName,
                $salesForceAttributeName
            );
            $object->addData($itemData);
            $this->resource->save($object);
        }

        $this->moduleDataSetup->endSetup();
    }
}
