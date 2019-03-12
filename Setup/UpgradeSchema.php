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

        $this->version_2_4_9($context, $setup);

        $this->addEntityQueue($context, $setup);

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
                Table::ACTION_CASCADE
            );
    }

    protected function version_2_4_9(ModuleContextInterface $context, SchemaSetupInterface $setup)
    {
        if (version_compare($context->getVersion(), '2.4.9') >= 0) {
            return;
        }

        $setup->getConnection()
            ->addColumn($setup->getTable('salesforce_objects'), 'store_id', [
                'type' => Table::TYPE_SMALLINT,
                'unsigned' => true,
                'nullable' => false,
                'default' => 0,
                'comment' => 'Store ID'
            ]);

        $setup->getConnection()
            ->dropIndex(
                $setup->getTable('salesforce_objects'),
                $setup->getIdxName(
                    'salesforce_objects',
                    ['entity_id', 'salesforce_type', 'magento_type', 'website_id'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                )
            );

        $setup->getConnection()
            ->addIndex(
                $setup->getTable('salesforce_objects'),
                $setup->getIdxName(
                    'salesforce_objects',
                    ['entity_id', 'salesforce_type', 'magento_type', 'website_id', 'store_id'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['entity_id', 'salesforce_type', 'magento_type', 'website_id', 'store_id'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
            );

        $setup->getConnection()
            ->addForeignKey(
                $setup->getFkName('salesforce_objects', 'store_id', 'store', 'store_id'),
                $setup->getTable('salesforce_objects'),
                'store_id',
                $setup->getTable('store'),
                'store_id',
                Table::ACTION_CASCADE
            );
    }

    /**
     * Add Entity Queue
     *
     * @param ModuleContextInterface $context
     * @param SchemaSetupInterface $setup
     * @throws \Zend_Db_Exception
     */
    protected function addEntityQueue(ModuleContextInterface $context, SchemaSetupInterface $setup)
    {
        if (version_compare($context->getVersion(), '2.5.0') >= 0) {
            return;
        }

        $table = $setup->getConnection()
            ->newTable($setup->getTable('tnw_salesforce_entity_queue'))
            ->addColumn('queue_id', Table::TYPE_INTEGER, null, [
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary' => true
            ], 'Queue Id')
            ->addColumn('entity_id', Table::TYPE_INTEGER, null, [
                'nullable' => false,
            ], 'Entity Id')
            ->addColumn('entity_load', Table::TYPE_TEXT, 255, [
                'nullable' => false,
            ], 'Entity Load')
            ->addColumn('entity_load_additional', Table::TYPE_TEXT, 1024, [
                'nullable' => true,
            ], 'Entity Load')
            ->addColumn('entity_type', Table::TYPE_TEXT, 255, [
                'nullable' => false
            ], 'Entity Type')
            ->addColumn('object_type', Table::TYPE_TEXT, 255, [
                'nullable' => false
            ], 'Object Type')
            ->addColumn('sync_type', Table::TYPE_INTEGER, null, [
                'unsigned' => true,
                'nullable' => false,
                'default' => 0
            ], 'Sync Type')
            ->addColumn('sync_attempt', Table::TYPE_INTEGER, null, [
                'unsigned' => true,
                'nullable' => false,
                'default' => 0
            ], 'Sync Attempt')
            ->addColumn('sync_at', Table::TYPE_DATETIME, null, [
                'nullable' => true
            ], 'When synced')
            ->addColumn('status', Table::TYPE_TEXT, 255, [
                'nullable' => false,
                'default' => 'new'
            ], 'Status')
            ->addColumn('message', Table::TYPE_TEXT, 1024, [
                'nullable' => true
            ], 'Message')
            ->addColumn('code', Table::TYPE_TEXT, 255, [
                'nullable' => false,
            ], 'Code')
            ->addColumn('description', Table::TYPE_TEXT, 255, [
                'nullable' => false,
            ], 'Description')
            ->addColumn('website_id', Table::TYPE_SMALLINT, null, [
                'unsigned' => true,
                'nullable' => false
            ], 'Website Id')
            ->addColumn('transaction_uid', Table::TYPE_TEXT, 32, [
                'nullable' => true,
                'default' => null
            ], 'Transaction Uid')
            ->addColumn('additional_data', Table::TYPE_TEXT, 1024, [
                'nullable' => true
            ], 'Additional Data')
            ->addColumn('created_at', Table::TYPE_TIMESTAMP, null, [
                'nullable' => false,
                'default' => Table::TIMESTAMP_INIT
            ], 'When create')
            ->addIndex(
                $setup->getIdxName('tnw_salesforce_entity_queue', ['code', 'entity_id', 'entity_load']),
                ['code', 'entity_id', 'entity_load']
            )
            ->addIndex(
                $setup->getIdxName('tnw_salesforce_entity_queue', ['transaction_uid', 'code', 'status', 'website_id']),
                ['transaction_uid', 'code', 'status', 'website_id']
            )
            ->addForeignKey(
                $setup->getFkName('tnw_salesforce_entity_queue', 'website_id', 'store_website', 'website_id'),
                'website_id',
                $setup->getTable('store_website'),
                'website_id',
                Table::ACTION_CASCADE
            )
        ;

        $setup->getConnection()
            ->createTable($table);

        $table = $setup->getConnection()
            ->newTable($setup->getTable('tnw_salesforce_entity_queue_relation'))
            ->addColumn('queue_id', Table::TYPE_INTEGER, null, [
                'unsigned' => true,
                'nullable' => false,
            ], 'Queue Id')
            ->addColumn('parent_id', Table::TYPE_INTEGER, null, [
                'unsigned' => true,
                'nullable' => false,
            ], 'Parent Id')
            ->addIndex(
                $setup->getIdxName(
                    'tnw_salesforce_entity_queue_relation',
                    ['queue_id', 'parent_id'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['queue_id', 'parent_id'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->addForeignKey(
                $setup->getFkName(
                    'tnw_salesforce_entity_queue_relation',
                    'queue_id',
                    'tnw_salesforce_entity_queue',
                    'queue_id'
                ),
                'queue_id',
                $setup->getTable('tnw_salesforce_entity_queue'),
                'queue_id',
                Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $setup->getFkName(
                    'tnw_salesforce_entity_queue_relation',
                    'parent_id',
                    'tnw_salesforce_entity_queue',
                    'queue_id'
                ),
                'parent_id',
                $setup->getTable('tnw_salesforce_entity_queue'),
                'queue_id',
                Table::ACTION_CASCADE
            )
        ;

        $setup->getConnection()
            ->createTable($table);
    }
}
