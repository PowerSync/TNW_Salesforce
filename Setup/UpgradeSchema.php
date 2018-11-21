<?php
namespace TNW\SalesForce\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

/**
 * Class UpgradeSchema
 * @package TNW\SalesForce\Setup
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Zend_Db_Exception
     */
    public function upgrade(SchemaSetupInterface $setup,
        ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '0.1.0.2') < 0) {
            /** add  column 'is_default' to tnw_salesforce_mapper */
            $setup->getConnection()->addColumn(
                $setup->getTable('tnw_salesforce_mapper'),
                'is_default',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'default' => 0,
                    'comment' => 'Default Map Field'
                ]
            );

            $setup->getConnection()->modifyColumn(
                $setup->getTable('tnw_salesforce_mapper'),
                'magento_attribute_name',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255
                ]
            );

            $setup->getConnection()->modifyColumn(
                $setup->getTable('tnw_salesforce_mapper'),
                'attribute_type',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255
                ]
            );

            $setup->getConnection()->modifyColumn(
                $setup->getTable('tnw_salesforce_mapper'),
                'default_value',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255
                ]
            );

            $setup->getConnection()->modifyColumn(
                $setup->getTable('tnw_salesforce_mapper'),
                'object_type',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255
                ]
            );

            $setup->getConnection()->modifyColumn(
                $setup->getTable('tnw_salesforce_mapper'),
                'magento_entity_type',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255
                ]
            );

            $setup->getConnection()->modifyColumn(
                $setup->getTable('tnw_salesforce_mapper'),
                'salesforce_attribute_name',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255
                ]
            );

            $setup->getConnection()->addIndex(
                $setup->getTable('tnw_salesforce_mapper'),
                'salesforce_business_mapper_index_unique',
                [
                    'object_type',
                    'magento_entity_type',
                    'magento_attribute_name',
                    'salesforce_attribute_name'
                ],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
            );
        }

        if (version_compare($context->getVersion(), '2.0.10') < 0) {
            $table = $setup->getConnection()
                ->newTable($setup->getTable('tnw_salesforce_log'))
                ->addColumn('id', Table::TYPE_BIGINT, null, [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary' => true
                ], 'Log ID')
                ->addColumn('transaction_uid', Table::TYPE_TEXT, 32, [
                    'nullable' => true,
                    'default' => null
                ], 'Transaction')
                ->addColumn('level', Table::TYPE_SMALLINT, null, [
                    'unsigned' => true,
                    'nullable' => true,
                    'default' => null,
                ], 'Level')
                ->addColumn('website_id', Table::TYPE_SMALLINT, null, [
                    'unsigned' => true,
                    'nullable' => true,
                    'default' => null,
                ], 'Website')
                ->addColumn('message', Table::TYPE_TEXT, '64k', [
                    'nullable' => true,
                    'default' => null
                ], 'Message')
                ->addColumn('created_at', Table::TYPE_TIMESTAMP, null, [
                    'nullable' => false,
                    'default' => Table::TIMESTAMP_INIT
                ], 'Create At')
                ->addIndex(
                    $setup->getIdxName('tnw_system_log', ['website_id']),
                    ['website_id']
                )
                ->addForeignKey(
                    $setup->getFkName('tnw_system_log', 'website_id', 'store_website', 'website_id'),
                    'website_id', $setup->getTable('store_website'), 'website_id', Table::ACTION_CASCADE
                )
            ;

            $setup->getConnection()
                ->createTable($table);
        }

        $this->version_2_1_0($context, $setup);

        $this->version_2_4_8($context, $setup);

        $setup->endSetup();
    }

    protected function version_2_1_0(ModuleContextInterface $context, SchemaSetupInterface $setup)
    {
        if (version_compare($context->getVersion(), '2.1.0') >= 0) {
            return;
        }

        $table = $setup->getConnection()
            ->newTable($setup->getTable('salesforce_objects'))
            ->addColumn('entity_id', Table::TYPE_INTEGER, null, [
                'unsigned' => true,
                'nullable' => false,
            ], 'Entity Id')
            ->addColumn('object_id', Table::TYPE_TEXT, 255, [
                'nullable' => true,
            ], 'Object Id')
            ->addColumn('magento_type', Table::TYPE_TEXT, 255, [
                'nullable' => false,
            ], 'Magento Type')
            ->addColumn('salesforce_type', Table::TYPE_TEXT, 255, [
                'nullable' => true,
                'default' => null,
            ], 'Salesforce Type')
            ->addColumn('status', Table::TYPE_SMALLINT, null, [
                'nullable' => false,
                'default' => false,
            ], 'Status')
            ->addIndex(
                $setup->getIdxName(
                    'salesforce_objects',
                    ['entity_id', 'salesforce_type', 'magento_type'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['entity_id', 'salesforce_type', 'magento_type'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )
        ;

        $setup->getConnection()
            ->createTable($table);
    }

    protected function version_2_4_8(ModuleContextInterface $context, SchemaSetupInterface $setup)
    {
        if (version_compare($context->getVersion(), '2.4.8') >= 0) {
            return;
        }

        $setup->getConnection()
            ->addColumn($setup->getTable('salesforce_objects'), 'website_id', [
                'type' => Table::TYPE_SMALLINT,
                'unsigned' => true,
                'nullable' => false,
                'default' => 0,
                'comment' => 'Website ID'
            ]);

        $setup->getConnection()
            ->dropIndex(
                $setup->getTable('salesforce_objects'),
                $setup->getIdxName(
                    'salesforce_objects',
                    ['entity_id', 'salesforce_type', 'magento_type'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                )
            );

        $setup->getConnection()
            ->addIndex(
                $setup->getTable('salesforce_objects'),
                $setup->getIdxName(
                    'salesforce_objects',
                    ['entity_id', 'salesforce_type', 'magento_type', 'website_id'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['entity_id', 'salesforce_type', 'magento_type', 'website_id'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
            );

        $setup->getConnection()
            ->addForeignKey(
                $setup->getFkName('salesforce_objects', 'website_id', 'store_website', 'website_id'),
                $setup->getTable('salesforce_objects'),
                'website_id',
                $setup->getTable('store_website'),
                'website_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            );
    }
}
