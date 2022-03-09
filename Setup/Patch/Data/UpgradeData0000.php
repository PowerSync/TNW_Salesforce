<?php

declare(strict_types=1);

namespace TNW\Salesforce\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use TNW\Salesforce\Setup\SalesforceSetup;
use TNW\Salesforce\Setup\SalesforceSetupFactory;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class UpgradeData0000 implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @var SalesforceSetupFactory
     */
    private $salesforceSetupFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param SalesforceSetupFactory $salesforceSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        SalesforceSetupFactory   $salesforceSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->salesforceSetupFactory = $salesforceSetupFactory;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [InstallData::class];
    }

    public static function getVersion()
    {
        return '0.0.0.0';
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $salesforceSetup = $this->salesforceSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $entityAttributes = [
            Customer::ENTITY => [
                'sforce_id' => [
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'is_filterable_in_grid' => true,
                    'is_searchable_in_grid' => true,
                ],
                'sforce_account_id' => [
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'is_filterable_in_grid' => true,
                    'is_searchable_in_grid' => true,
                ],
                'sync_status' => [
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'is_filterable_in_grid' => true,
                    'is_searchable_in_grid' => false,
                ],
            ]
        ];
        $this->updateAttributes($entityAttributes, $salesforceSetup);
        $this->moduleDataSetup->endSetup();
    }

    /**
     * @param array $entityAttributes
     * @param SalesforceSetup $salesforceSetup
     * @return void
     */
    private function updateAttributes(array $entityAttributes, SalesforceSetup $salesforceSetup)
    {
        foreach ($entityAttributes as $entityType => $attributes) {
            foreach ($attributes as $attributeCode => $attributeData) {
                foreach ($attributeData as $attributeField => $value) {
                    $salesforceSetup->updateAttribute(
                        $entityType,
                        $attributeCode,
                        $attributeField,
                        $value
                    );
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
