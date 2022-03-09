<?php

declare(strict_types=1);

namespace TNW\Salesforce\Setup\Patch\Data;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Model\Customer;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use TNW\Salesforce\Setup\SalesforceSetup;
use TNW\Salesforce\Setup\SalesforceSetupFactory;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class UpgradeData0101 implements DataPatchInterface, PatchVersionInterface
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
        return [AddSalesForceAttribute::class];
    }

    public static function getVersion()
    {
        return '0.1.0.1';
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
                'sync_status' => [
                    'is_visible' => false,
                    'source_model' => 'TNW\Salesforce\Model\Customer\Attribute\Source\SyncStatus'
                ],
                'sforce_account_id' => [
                    'is_visible' => false
                ],
                'sforce_id' => [
                    'is_visible' => false
                ],
            ]
        ];
        $this->updateAttributes($entityAttributes, $salesforceSetup);
        $salesforceSetup->addAttributeGroup(
            Customer::ENTITY,
            CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER,
            'Salesforce'
        );
        $attributeCodes = ['sforce_id', 'sforce_account_id', 'sync_status'];
        foreach ($attributeCodes as $code) {
            $salesforceSetup->addAttributeToSet(
                Customer::ENTITY,
                CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER,
                'Salesforce',
                $code
            );
        }
        $this->moduleDataSetup->endSetup();
    }

    /**
     * @param array $entityAttributes
     * @param SalesforceSetup $salesforceSetup
     * @return void
     */
    protected function updateAttributes(array $entityAttributes, SalesforceSetup $salesforceSetup)
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
