<?php

namespace TNW\Salesforce\Setup;

use Magento\Customer\Model\Indexer\Address\AttributeProvider;
use Magento\Framework\DB\Select;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Config;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Eav\Setup\EavSetup;
use TNW\Salesforce\Model\Customer\Map;

/**
 * Class UpgradeData
 * @package TNW\Salesforce\Setup
 */
class UpgradeData implements UpgradeDataInterface
{
    /** @var SalesforceSetupFactory */
    protected $salesforceSetupFactory;

    /** @var IndexerRegistry */
    protected $indexerRegistry;

    /** @var Config */
    protected $eavConfig;

    /** @var EavSetup */
    protected $eavSetup;

    /**
     * @param SalesforceSetupFactory $salesforceSetupFactory
     * @param IndexerRegistry $indexerRegistry
     * @param Config $eavConfig
     */
    public function __construct(
        SalesforceSetupFactory $salesforceSetupFactory,
        IndexerRegistry $indexerRegistry,
        Config $eavConfig,
        EavSetup $eavSetup
    ) {
        $this->salesforceSetupFactory = $salesforceSetupFactory;
        $this->indexerRegistry = $indexerRegistry;
        $this->eavConfig = $eavConfig;
        $this->eavSetup = $eavSetup;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        if (version_compare($context->getVersion(), '0.0.0.0') < 0) {
            $salesforceSetup = $this->salesforceSetupFactory->create(['setup' => $setup]);

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
        }
        if (version_compare($context->getVersion(), '0.1.0.1') < 0) {
            $salesforceSetup = $this->salesforceSetupFactory->create(['setup' => $setup]);

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
            $salesforceSetup->addAttributeGroup(Customer::ENTITY, CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER,
                'Salesforce');
            $attributeCodes = array('sforce_id', 'sforce_account_id', 'sync_status');
            foreach ($attributeCodes as $code) {
                $salesforceSetup->addAttributeToSet(
                    Customer::ENTITY,
                    CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER,
                    'Salesforce',
                    $code
                );
            }
        }

        if (version_compare($context->getVersion(), '0.1.0.2') < 0) {
            /**  prepare default mapping for customer to contact */
            $customerToContact = [
                [
                    'magento_attribute_name' => 'sforce_id',
                    'salesforce_attribute_name' => 'Id',
                    'attribute_type' => 'string',
                    'object_type' => 'Contact',
                    'magento_entity_type' => 'customer',
                    'is_default' => 1
                ],
                [
                    'magento_attribute_name' => 'lastname',
                    'salesforce_attribute_name' => 'LastName',
                    'attribute_type' => 'string',
                    'object_type' => 'Contact',
                    'is_default' => 1
                ],
                [
                    'magento_attribute_name' => 'email',
                    'salesforce_attribute_name' => 'Email',
                    'attribute_type' => 'string',
                    'object_type' => 'Contact',
                    'is_default' => 1
                ],
                [
                    'magento_attribute_name' => 'firstname',
                    'salesforce_attribute_name' => 'FirstName',
                    'attribute_type' => 'string',
                    'object_type' => 'Contact',
                ],
                [
                    'magento_attribute_name' => 'dob',
                    'salesforce_attribute_name' => 'Birthdate',
                    'attribute_type' => 'string',
                    'object_type' => 'Contact'
                ]

            ];

            foreach ($customerToContact as $magento_attr) {

                $attribute = $this->eavSetup->getAttribute(\Magento\Customer\Model\Customer::ENTITY,
                    $magento_attr['magento_attribute_name']
                );
                $magento_attr['attribute_id'] = $attribute['attribute_id'];
                $magento_attr['magento_entity_type'] = 'customer';
                $setup->getConnection()->insertOnDuplicate($setup->getTable('tnw_salesforce_mapper'), $magento_attr);
            }

            /** Customer Address to Account and Contact */
            $customerShippingToContact = [
                [
                    'magento_attribute_name' => 'street',
                    'salesforce_attribute_name' => 'MailingStreet',
                    'attribute_type' => 'string',
                    'object_type' => 'Contact'
                ],
                [
                    'magento_attribute_name' => 'city',
                    'salesforce_attribute_name' => 'MailingCity',
                    'attribute_type' => 'string',
                    'object_type' => 'Contact'
                ],
                [
                    'magento_attribute_name' => 'region',
                    'salesforce_attribute_name' => 'MailingState',
                    'attribute_type' => 'string',
                    'object_type' => 'Contact'
                ],
                [
                    'magento_attribute_name' => 'postcode',
                    'salesforce_attribute_name' => 'MailingPostalCode',
                    'attribute_type' => 'string',
                    'object_type' => 'Contact'
                ],
                [
                    'magento_attribute_name' => 'country_id',
                    'salesforce_attribute_name' => 'MailingCountry',
                    'attribute_type' => 'string',
                    'object_type' => 'Contact'
                ],
                [
                    'magento_attribute_name' => 'telephone',
                    'salesforce_attribute_name' => 'Phone',
                    'attribute_type' => 'string',
                    'object_type' => 'Contact'
                ]
            ];

            foreach ($customerShippingToContact as $magento_attr) {

                $attribute = $this->eavSetup->getAttribute('customer_address',
                    $magento_attr['magento_attribute_name']
                );
                $magento_attr['magento_entity_type'] = 'customer_address/shipping';
                $magento_attr['attribute_id'] = $attribute['attribute_id'];
                $setup->getConnection()->insertOnDuplicate($setup->getTable('tnw_salesforce_mapper'), $magento_attr);
            }

            /** @var  array $customerBillingToContact */
            $customerBillingToContact = [
                [
                    'magento_attribute_name' => 'street',
                    'salesforce_attribute_name' => 'OtherStreet',
                    'attribute_type' => 'string',
                    'object_type' => 'Contact'
                ],
                [
                    'magento_attribute_name' => 'city',
                    'salesforce_attribute_name' => 'OtherCity',
                    'attribute_type' => 'string',
                    'object_type' => 'Contact'
                ],
                [
                    'magento_attribute_name' => 'region_id',
                    'salesforce_attribute_name' => 'OtherState',
                    'attribute_type' => 'string',
                    'object_type' => 'Contact'
                ],
                [
                    'magento_attribute_name' => 'postcode',
                    'salesforce_attribute_name' => 'OtherPostalCode',
                    'attribute_type' => 'string',
                    'object_type' => 'Contact'
                ],
                [
                    'magento_attribute_name' => 'country_id',
                    'salesforce_attribute_name' => 'OtherCountry',
                    'attribute_type' => 'string',
                    'object_type' => 'Contact'
                ],
                [
                    'magento_attribute_name' => 'telephone',
                    'salesforce_attribute_name' => 'OtherPhone',
                    'attribute_type' => 'string',
                    'object_type' => 'Contact'
                ]
            ];

            foreach ($customerBillingToContact as $magento_attr) {

                $attribute = $this->eavSetup->getAttribute('customer_address',
                    $magento_attr['magento_attribute_name']
                );
                $magento_attr['attribute_id'] = $attribute['attribute_id'];
                $magento_attr['magento_entity_type'] = 'customer_address/billing';
                $setup->getConnection()->insertOnDuplicate($setup->getTable('tnw_salesforce_mapper'), $magento_attr);
            }

            /**  prepare default mapping for customer to account */
            $customerToAccount = [
                [
                    'magento_attribute_name' => 'sforce_id',
                    'salesforce_attribute_name' => 'Id',
                    'attribute_type' => 'string',
                    'object_type' => 'Account',
                    'magento_entity_type' => 'customer',
                    'is_default' => 1
                ],
                [
                    'magento_attribute_name' => 'firstname',
                    'salesforce_attribute_name' => 'FirstName',
                    'attribute_type' => 'string',
                    'object_type' => 'Account'
                ],
                [
                    'magento_attribute_name' => 'lastname',
                    'salesforce_attribute_name' => 'LastName',
                    'attribute_type' => 'string',
                    'object_type' => 'Account'
                ],
                [
                    'magento_attribute_name' => 'dob',
                    'salesforce_attribute_name' => 'Birthdate',
                    'attribute_type' => 'string',
                    'object_type' => 'Account'
                ]

            ];

            foreach ($customerToAccount as $magento_attr) {

                $attribute = $this->eavSetup->getAttribute(\Magento\Customer\Model\Customer::ENTITY,
                    $magento_attr['magento_attribute_name']
                );
                $magento_attr['attribute_id'] = $attribute['attribute_id'];
                $magento_attr['magento_entity_type'] = 'customer';
                $setup->getConnection()->insertOnDuplicate($setup->getTable('tnw_salesforce_mapper'), $magento_attr);
            }

            /** Customer Address to Account */

            /** @var array $customerShippingToAccount */
            $customerShippingToAccount = [
                [
                    'magento_attribute_name' => 'street',
                    'salesforce_attribute_name' => 'ShippingStreet',
                    'attribute_type' => 'string',
                    'object_type' => 'Account'
                ],
                [
                    'magento_attribute_name' => 'city',
                    'salesforce_attribute_name' => 'ShippingCity',
                    'attribute_type' => 'string',
                    'object_type' => 'Account'
                ],
                [
                    'magento_attribute_name' => 'region_id',
                    'salesforce_attribute_name' => 'ShippingState',
                    'attribute_type' => 'string',
                    'object_type' => 'Account'
                ],
                [
                    'magento_attribute_name' => 'postcode',
                    'salesforce_attribute_name' => 'ShippingPostalCode',
                    'attribute_type' => 'string',
                    'object_type' => 'Account'
                ],
                [
                    'magento_attribute_name' => 'country_id',
                    'salesforce_attribute_name' => 'ShippingCountry',
                    'attribute_type' => 'string',
                    'object_type' => 'Account'
                ],
                [
                    'magento_attribute_name' => 'telephone',
                    'salesforce_attribute_name' => 'Phone',
                    'attribute_type' => 'string',
                    'object_type' => 'Account'
                ]
            ];

            foreach ($customerShippingToAccount as $magento_attr) {

                $attribute = $this->eavSetup->getAttribute('customer_address',
                    $magento_attr['magento_attribute_name']
                );
                $magento_attr['magento_entity_type'] = 'customer_address/shipping';
                $magento_attr['attribute_id'] = $attribute['attribute_id'];
                $setup->getConnection()->insertOnDuplicate($setup->getTable('tnw_salesforce_mapper'), $magento_attr);
            }

            /** @var  array $customerBillingToContact */
            $customerBillingToAccount = [
                [
                    'magento_attribute_name' => 'street',
                    'salesforce_attribute_name' => 'BillingStreet',
                    'attribute_type' => 'string',
                    'object_type' => 'Account'
                ],
                [
                    'magento_attribute_name' => 'city',
                    'salesforce_attribute_name' => 'BillingCity',
                    'attribute_type' => 'string',
                    'object_type' => 'Account'
                ],
                [
                    'magento_attribute_name' => 'region_id',
                    'salesforce_attribute_name' => 'BillingState',
                    'attribute_type' => 'string',
                    'object_type' => 'Account'
                ],
                [
                    'magento_attribute_name' => 'postcode',
                    'salesforce_attribute_name' => 'BillingPostalCode',
                    'attribute_type' => 'string',
                    'object_type' => 'Account'
                ],
                [
                    'magento_attribute_name' => 'country_id',
                    'salesforce_attribute_name' => 'BillingCountry',
                    'attribute_type' => 'string',
                    'object_type' => 'Account'
                ],
            ];

            foreach ($customerBillingToAccount as $magento_attr) {

                $attribute = $this->eavSetup->getAttribute('customer_address',
                    $magento_attr['magento_attribute_name']
                );
                $magento_attr['attribute_id'] = $attribute['attribute_id'];
                $magento_attr['magento_entity_type'] = 'customer_address/billing';
                $setup->getConnection()->insertOnDuplicate($setup->getTable('tnw_salesforce_mapper'), $magento_attr);
            }
        }

        if (version_compare($context->getVersion(), '0.1.0.3') < 0) {
            $customerToContact = [
                'magento_attribute_name' => 'Id',
                'salesforce_attribute_name' => Map::SFORCE_BASIC_PREFIX . Map::SFORCE_MAGENTO_ID,
                'attribute_type' => 'string',
                'object_type' => 'Contact',
                'magento_entity_type' => 'customer',
                'is_default' => 1
            ];


            $setup->getConnection()->insertOnDuplicate($setup->getTable('tnw_salesforce_mapper'), $customerToContact);

            $customerToAccount = [
                'magento_attribute_name' => 'Id',
                'salesforce_attribute_name' => Map::SFORCE_BASIC_PREFIX . Map::SFORCE_MAGENTO_ID,
                'attribute_type' => 'string',
                'object_type' => 'Account',
                'magento_entity_type' => 'customer',
                'is_default' => 1
            ];

            $setup->getConnection()->insertOnDuplicate($setup->getTable('tnw_salesforce_mapper'), $customerToAccount);
        }

        if (version_compare($context->getVersion(), '2.0.2') < 0) {
            $this->updateAttributeTypes($setup);
        }

        $this->version_2_0_2($context);

        $this->version_2_0_3($context, $setup);

        $this->version_2_1_1($context, $setup);

        $this->version_2_1_8($context, $setup);

        $this->version_2_1_11($context, $setup);

        $setup->endSetup();
    }

    /**
     * @param ModuleContextInterface $context
     */
    protected function version_2_0_2(ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '2.0.2') < 0) {
            $this->eavSetup->updateAttribute(
                Customer::ENTITY,
                'sync_status',
                'frontend_input',
                'select'
            );

            $this->eavSetup->updateAttribute(
                Customer::ENTITY,
                'sync_status',
                'attribute_code',
                'sforce_sync_status'
            );
        }
    }

    /**
     * @param ModuleContextInterface $context
     */
    protected function version_2_0_3(ModuleContextInterface $context, ModuleDataSetupInterface $setup)
    {
        if (version_compare($context->getVersion(), '2.0.3') < 0) {
            // Delete custom grid settings to change grid columns order
            $setup->getConnection()->delete($setup->getTable('ui_bookmark'), [
                "namespace = ?" => 'customer_listing',
            ]);
        }
    }

    protected function version_2_1_1(ModuleContextInterface $context, ModuleDataSetupInterface $setup)
    {
        if (version_compare($context->getVersion(), '2.1.1') >= 0) {
            return;
        }

        $salesforceSetup = $this->salesforceSetupFactory->create(['setup' => $setup]);
        $this->updateAttributes([
            Customer::ENTITY => [
                'sforce_id' => [
                    'is_used_in_grid' => false,
                    'is_searchable_in_grid' => false,
                ],
                'sforce_account_id' => [
                    'is_used_in_grid' => false,
                    'is_searchable_in_grid' => false,
                ],
                'sforce_sync_status' => [
                    'is_used_in_grid' => false,
                    'is_searchable_in_grid' => false,
                ],
            ]
        ], $salesforceSetup);
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
     * @param ModuleDataSetupInterface $setup
     */
    protected function updateAttributeTypes(ModuleDataSetupInterface $setup)
    {
        /** @var Select $select */
        $select = $setup->getConnection()
            ->select()
            ->from($setup->getTable('tnw_salesforce_mapper'))
            ->where("object_type in (?)", ['Contact', 'Account']);

        /** @var array $customerToContactsAndAccounts */
        $customerToContactsAndAccounts = $setup->getConnection()
            ->fetchAssoc($select);

        foreach ($customerToContactsAndAccounts as $customerToContactAndAccount) {
            switch ($customerToContactAndAccount['magento_entity_type']) {
                case 'customer' :
                    $attribute = $this->eavSetup->getAttribute(
                        Customer::ENTITY,
                        $customerToContactAndAccount['magento_attribute_name']
                    );
                    break;
                case 'customer_address/billing' :
                case 'customer_address/shipping' :
                    $attribute = $this->eavSetup->getAttribute(
                        AttributeProvider::ENTITY,
                        $customerToContactAndAccount['magento_attribute_name']
                    );
                    break;
            }

            if (isset($attribute['backend_type'])) {
                $customerToContactAndAccount['attribute_type'] =
                    $attribute['backend_type'];
            }

            $setup->getConnection()
                ->insertOnDuplicate(
                    $setup->getTable('tnw_salesforce_mapper'),
                    $customerToContactAndAccount
                );
        }
    }

    /**
     * @param ModuleContextInterface $context
     * @param ModuleDataSetupInterface $setup
     */
    protected function version_2_1_8(ModuleContextInterface $context, ModuleDataSetupInterface $setup)
    {
        if (version_compare($context->getVersion(), '2.1.8') >= 0) {
            return;
        }

        $defaultMap = [
            [
                'magento_attribute_name' => 'sforce_account_id',
                'salesforce_attribute_name' => 'AccountId',
                'object_type' => 'Contact',
                'magento_entity_type' => 'customer',
                'default_value' => null,
                'attribute_type' => 'string',
                'is_default' => 1,
            ]
        ];

        $setup->getConnection()
            ->insertOnDuplicate($setup->getTable('tnw_salesforce_mapper'), $defaultMap);
    }


    /**
     * @param ModuleContextInterface $context
     * @param ModuleDataSetupInterface $setup
     */
    protected function version_2_1_11(ModuleContextInterface $context, ModuleDataSetupInterface $setup)
    {
        if (version_compare($context->getVersion(), '2.1.11') >= 0) {
            return;
        }

        $defaultMap = [
            [
                'magento_attribute_name' => 'website_id',
                'salesforce_attribute_name' => 'tnw_mage_basic__Magento_Website__c',
                'attribute_type' => 'string',
                'object_type' => 'Contact',
                'magento_entity_type' => 'customer',
                'is_default' => 1,
            ],
            [
                'magento_attribute_name' => 'website_id',
                'salesforce_attribute_name' => 'tnw_mage_basic__Magento_Website__c',
                'attribute_type' => 'string',
                'object_type' => 'Lead',
                'magento_entity_type' => 'customer',
                'is_default' => 1,
            ],

        ];

        $setup->getConnection()
            ->insertOnDuplicate($setup->getTable('tnw_salesforce_mapper'), $defaultMap);
    }
}
