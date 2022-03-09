<?php

declare(strict_types=1);

namespace TNW\Salesforce\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class UpgradeData203 implements DataPatchInterface, PatchVersionInterface
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
        return [UpgradeData202::class];
    }

    public static function getVersion()
    {
        return '2.0.3';
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        // Delete custom grid settings to change grid columns order
        $this->moduleDataSetup->getConnection()->delete($this->moduleDataSetup->getTable('ui_bookmark'), [
            "namespace = ?" => 'customer_listing',
        ]);
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
