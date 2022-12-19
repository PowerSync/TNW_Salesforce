<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

class AddTestAttributes implements DataPatchInterface, PatchVersionInterface
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
    private $_eavSetupFactory;

    /**
     *
     * {@inheritdoc}
     * AddSalesForceAttribute constructor.
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->_moduleDataSetup = $moduleDataSetup;
        $this->_eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     *
     * @return array|string[]
     */
    public static function getDependencies()
    {
        return [];
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
        $eavSetup = $this->_eavSetupFactory->create(
            ['setup' => $this->_moduleDataSetup]
        );

        foreach (range('a', 'z') as $char) {
            for ($i = 1; $i <= 5; $i++) {
                $eavSetup->addAttribute(
                    Customer::ENTITY,
                    $char . $i,
                    [
                        'type' => 'varchar',
                        'required' => false,
                        'sort_order' => 1,
                        'visible' => true,
                        'system' => false,
                        'group' => 'Account Information',
                        'default' => null,
                        'label' => $char . $i
                    ]
                );

                //add Description into table
                $data = [
                    'magento_attribute_name'    => $char . $i,
                    'salesforce_attribute_name' => 'Id',
                    'attribute_type' => 'text',
                    'magento_entity_type' => 'customer',
                    'object_type' => 'Contact'
                ];
                $this->_moduleDataSetup->getConnection()->insertOnDuplicate(
                    $this->_moduleDataSetup->getTable('tnw_salesforce_mapper'),
                    $data
                );

            }
        }
    }

    /**
     *
     * @return string
     */
    public static function getVersion()
    {
        return '0.0.1';
    }
}
