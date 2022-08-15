<?php

namespace TNW\Salesforce\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use TNW\Salesforce\Setup\SalesforceSetupFactory;

class UpdateAccountMapping implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * ModuleDataSetupInterface
     *
     * @var ModuleDataSetupInterface
     */
    private $_moduleDataSetup;

    /**
     * EavSetupFactory
     *
     * @var EavSetupFactory
     */
    private $_salesForceSetupFactory;

    /**
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $salesforceSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        SalesforceSetupFactory $salesforceSetupFactory
    ) {
        $this->_moduleDataSetup = $moduleDataSetup;
        $this->_salesForceSetupFactory = $salesforceSetupFactory;
    }

    /**
     * {@inheritdoc}
     *
     * @return array|string[]
     */
    public static function getDependencies()
    {
        return [UpdateAttributeSalesForce::class];
    }

    /**
     * {@inheritdoc}
     *
     * @return array|string[]
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     *
     * @return DataPatchInterface|void
     */
    public function apply()
    {
        $setup = $this->_moduleDataSetup;
        $setup->startSetup();

        $connection = $setup->getConnection();

        $connection->delete(
            $setup->getTable('tnw_salesforce_mapper'),
            [
                "salesforce_attribute_name = 'Birthdate'",
                "magento_attribute_name = 'dob'",
                "object_type = 'Account'",
                "magento_entity_type = 'customer'"
            ]
        );

        $connection->delete(
            $setup->getTable('tnw_salesforce_mapper'),
            [
                "salesforce_attribute_name = 'FirstName'",
                "magento_attribute_name = 'firstname'",
                "object_type = 'Account'",
                "magento_entity_type = 'customer'"
            ]
        );

        $connection->delete(
            $setup->getTable('tnw_salesforce_mapper'),
            [
                "salesforce_attribute_name = 'LastName'",
                "magento_attribute_name = 'lastname'",
                "object_type = 'Account'",
                "magento_entity_type = 'customer'"
            ]
        );

        $setup->endSetup();
    }

    public function revert()
    {
        // Nothing to revert here
    }
}
