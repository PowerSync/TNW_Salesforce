<?php
declare(strict_types=1);

namespace TNW\Salesforce\Setup;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Model\Customer;
use Magento\Setup\Model\ObjectManagerProvider;
use Magento\Setup\Module\DataSetup;

/**
 * Class Uninstall
 * @package TNW\Salesforce\Setup
 */
class Uninstall implements UninstallInterface
{
    /**
     * Invoked when remove-data flag is set during module uninstall.
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
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
            $connection->prepareSqlCondition('attribute_code', ['in'=>[
                'sforce_sync_status',
                'sforce_account_id',
                'sforce_id',
            ]]),
        ]);

        $connection->dropTable($setup->getTable('tnw_salesforce_log'));
        $connection->dropTable($setup->getTable('tnw_salesforce_objects'));
    }
}
