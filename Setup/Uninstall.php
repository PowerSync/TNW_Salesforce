<?php

namespace TNW\Salesforce\Setup;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;

/**
 * Class Uninstall
 * @package TNW\Salesforce\Setup
 */
class Uninstall implements UninstallInterface
{
    /**
     * @var CustomerSetupFactory
     */
    private $customerSetupFactory;

    /**
     * @param CustomerSetupFactory $customerSetupFactory
     */
    public function __construct(
        CustomerSetupFactory $customerSetupFactory
    ) {
        $this->customerSetupFactory = $customerSetupFactory;
    }

    /**
     * Invoked when remove-data flag is set during module uninstall.
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $this->revertConfig($setup);
        $this->revertSchema($setup);
        $this->revertEavData($setup);

        $setup->endSetup();
    }

    /**
     * Revert config changes during uninstallation.
     *
     * @param SchemaSetupInterface $setup
     */
    private function revertConfig(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $wheres = [
            ['path LIKE (?)' => 'tnw_salesforce/%'],
            ['path LIKE (?)' => 'tnwsforce_general/%'],
            ['path LIKE (?)' => 'tnwsforce_customer/%'],
        ];
        foreach ($wheres as $where) {
            $connection->delete($setup->getTable('core_config_data'), $where);
        }
    }

    /**
     * Revert eav data changes during uninstallation.
     *
     * @param SchemaSetupInterface $setup
     */
    private function revertEavData(SchemaSetupInterface $setup)
    {
        $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);
        $customerAttributesToRemove = [
            'sforce_id',
            'sforce_account_id',
            'sforce_sync_status',
        ];
        foreach ($customerAttributesToRemove as $attributeCode) {
            if ($customerSetup->getAttributeId(Customer::ENTITY, $attributeCode)) {
                $customerSetup->removeAttribute(Customer::ENTITY, $attributeCode);
            }
        }
    }

    /**
     * Revert schema changes during uninstallation.
     *
     * @param SchemaSetupInterface $setup
     */
    private function revertSchema(SchemaSetupInterface $setup)
    {
        /** @var \Magento\Framework\DB\Adapter\Pdo\Mysql $connection */
        $connection = $setup->getConnection();
        $entityTypeSelect = $connection->select()
            ->from($setup->getTable('eav_entity_type'), ['entity_type_id', 'default_attribute_set_id'])
            ->where($connection->prepareSqlCondition('entity_type_code', Customer::ENTITY));

        $customerType = $connection->fetchRow($entityTypeSelect);

        $setup->getConnection()
            ->dropColumn($setup->getTable('store_website'), 'salesforce_id');

        $setup->getConnection()
            ->dropTable($setup->getTable('tnw_salesforce_mapper'));

        $connection->delete($setup->getTable('eav_attribute_group'), [
            $connection->prepareSqlCondition('attribute_group_name', 'Salesforce'),
            $connection->prepareSqlCondition('attribute_set_id', $customerType['default_attribute_set_id']),
        ]);

        $connection->delete($setup->getTable('eav_attribute'), [
            $connection->prepareSqlCondition('entity_type_id', $customerType['entity_type_id']),
            $connection->prepareSqlCondition('attribute_code', ['in' => [
                'sforce_sync_status',
                'sforce_account_id',
                'sforce_id',
            ]]),
        ]);

        $connection->dropTable($setup->getTable('tnw_salesforce_log'));
        $connection->dropTable($setup->getTable('tnw_salesforce_objects'));
    }
}
