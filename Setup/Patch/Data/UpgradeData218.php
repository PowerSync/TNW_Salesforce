<?php

declare(strict_types=1);

namespace TNW\Salesforce\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class UpgradeData218 implements DataPatchInterface, PatchVersionInterface
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
        return [UpgradeData211::class];
    }

    public static function getVersion()
    {
        return '2.1.8';
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
                'magento_attribute_name' => 'sforce_account_id',
                'salesforce_attribute_name' => 'AccountId',
                'object_type' => 'Contact',
                'magento_entity_type' => 'customer',
                'default_value' => null,
                'attribute_type' => 'string',
                'is_default' => 1,
            ]
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
