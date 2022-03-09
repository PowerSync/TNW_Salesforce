<?php

declare(strict_types=1);

namespace TNW\Salesforce\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use TNW\Salesforce\Model\Customer\Map;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class UpgradeData0103 implements DataPatchInterface, PatchVersionInterface
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
        return [UpgradeData0102::class];
    }

    public static function getVersion()
    {
        return '0.1.0.3';
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $customerToContact = [
            'magento_attribute_name' => 'Id',
            'salesforce_attribute_name' => Map::SFORCE_BASIC_PREFIX . Map::SFORCE_MAGENTO_ID,
            'attribute_type' => 'string',
            'object_type' => 'Contact',
            'magento_entity_type' => 'customer',
            'is_default' => 1
        ];

        $this->moduleDataSetup->getConnection()->insertOnDuplicate($this->moduleDataSetup->getTable('tnw_salesforce_mapper'), $customerToContact);

        $customerToAccount = [
            'magento_attribute_name' => 'Id',
            'salesforce_attribute_name' => Map::SFORCE_BASIC_PREFIX . Map::SFORCE_MAGENTO_ID,
            'attribute_type' => 'string',
            'object_type' => 'Account',
            'magento_entity_type' => 'customer',
            'is_default' => 1
        ];

        $this->moduleDataSetup->getConnection()->insertOnDuplicate($this->moduleDataSetup->getTable('tnw_salesforce_mapper'), $customerToAccount);
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
