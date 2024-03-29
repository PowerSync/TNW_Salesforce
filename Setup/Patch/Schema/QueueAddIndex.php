<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Setup\Patch\Schema;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Zend_Db_Statement_Interface;

class QueueAddIndex implements SchemaPatchInterface, PatchRevertableInterface
{
    /**
     * @var SchemaSetupInterface
     */
    private $schemaSetup;

    /**
     * QueueRelationForeignKeyFix constructor.
     * @param SchemaSetupInterface $schemaSetup
     */
    public function __construct(
        SchemaSetupInterface $schemaSetup
    )
    {
        $this->schemaSetup = $schemaSetup;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->schemaSetup->startSetup();

        $connection = $this->schemaSetup->getConnection();

        $queueTable = $this->schemaSetup->getTable('tnw_salesforce_entity_queue');

        $connection->addIndex(
            $queueTable,
            $this->schemaSetup->getIdxName(
                $queueTable,
                [
                    'sync_type',
                    'sync_attempt',
                    'website_id',
                    'status',
                    'transaction_uid'
                ]
            ),
            [
                'sync_type',
                'sync_attempt',
                'website_id',
                'status',
                'transaction_uid'
            ]
        );

        $this->schemaSetup->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    public function revert()
    {
        // Please check \Magento\Framework\Setup\Patch\PatchRevertableInterface comments before adding any code here.
    }
}
