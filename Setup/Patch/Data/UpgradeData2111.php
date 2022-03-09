<?php

declare(strict_types=1);

namespace TNW\Salesforce\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class UpgradeData2111 implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [UpgradeData218::class];
    }

    public static function getVersion()
    {
        return '2.1.11';
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $defaultMap = [
            [
                'magento_attribute_name' => 'website_id',
                'salesforce_attribute_name' => 'tnw_mage_basic__Magento_Website__c',
                'attribute_type' => 'string',
                'object_type' => 'Contact',
                'magento_entity_type' => 'customer',
                'is_default' => 1,
            ],
            [
                'magento_attribute_name' => 'website_id',
                'salesforce_attribute_name' => 'tnw_mage_basic__Magento_Website__c',
                'attribute_type' => 'string',
                'object_type' => 'Lead',
                'magento_entity_type' => 'customer',
                'is_default' => 1,
            ],

        ];

        $this->moduleDataSetup->getConnection()
            ->insertOnDuplicate($this->moduleDataSetup->getTable('tnw_salesforce_mapper'), $defaultMap);
        $this->moduleDataSetup->endSetup();
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
