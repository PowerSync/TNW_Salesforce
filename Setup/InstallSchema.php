<?php
declare(strict_types=1);

namespace TNW\Salesforce\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        /**
         * Create table 'tnw_salesforce_mapper'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('tnw_salesforce_mapper'))
            ->addColumn(
                'map_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Map Id'
            )
            ->addColumn(
                'magento_attribute_name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                [],
                'Magento Attribute Name'
            )
            ->addColumn(
                'salesforce_attribute_name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                [],
                'Salesforce Attribute Name'
            )
            ->addColumn(
                'attribute_type',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                [],
                'Attribute Type'
            )
            ->addColumn(
                'attribute_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [],
                'Attribute Id'
            )
            ->addColumn(
                'default_value',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                [],
                'Default Attribute Value'
            )
            ->addColumn(
                'object_type',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                [],
                'Object Type'
            )
            ->addColumn(
                'magento_entity_type',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                [],
                'Magento Entity Type'
            )
            ->addIndex(
                $installer->getIdxName('tnw_salesforce_mapper', ['map_id']),
                ['map_id']
            )
            ->setComment('TNW Magento<->Salesforce mapping');

        $installer->getConnection()->createTable($table);

        $installer->getConnection()->addColumn(
            $installer->getTable('store_website'),
            'salesforce_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 50,
                'comment' => 'Salesforce Id'
            ]
        );

        $installer->endSetup();
    }
}
