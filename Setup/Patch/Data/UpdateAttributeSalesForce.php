<?php

namespace TNW\Salesforce\Setup\Patch\Data;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Indexer\Address\AttributeProvider;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use TNW\Salesforce\Model\Customer\Attribute\Source\SyncStatus;
use TNW\Salesforce\Model\Customer\Map;
use TNW\Salesforce\Setup\SalesforceSetup;
use TNW\Salesforce\Setup\SalesforceSetupFactory;
use Zend_Db_Expr;

class UpdateAttributeSalesForce implements DataPatchInterface, PatchRevertableInterface
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
        return [AddSalesForceAttribute::class];
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
        $this->_moduleDataSetup->getConnection()->startSetup();
        $setup = $this->_moduleDataSetup;
        $salesForceSetup = $this->_salesForceSetupFactory->create(
            [
                'setup' => $setup
            ]
        );

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

        $this->updateAttributes($entityAttributes, $salesForceSetup);

        //0.1.0.1
        $entityAttributes = [
            Customer::ENTITY => [
                'sync_status' => [
                    'is_visible' => false,
                    'source_model' => SyncStatus::class
                ],
                'sforce_account_id' => [
                    'is_visible' => false
                ],
                'sforce_id' => [
                    'is_visible' => false
                ],
            ]
        ];
        $this->updateAttributes($entityAttributes, $salesForceSetup);
        $salesForceSetup->addAttributeGroup(
                Customer::ENTITY,
                CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER,
                'Salesforce'
            );
        $attributeCodes = ['sforce_id', 'sforce_account_id', 'sync_status'];
        foreach ($attributeCodes as $code) {
            $salesForceSetup->addAttributeToSet(
                Customer::ENTITY,
                CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER,
                'Salesforce',
                $code
            );
        }

        //0.1.0.2
        // prepare default mapping for customer to contact
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
            $attribute = $salesForceSetup->getAttribute(
                Customer::ENTITY,
                $magento_attr['magento_attribute_name']
            );
            $magento_attr['attribute_id'] = $attribute['attribute_id'];
            $magento_attr['magento_entity_type'] = 'customer';
            $setup->getConnection()->insertOnDuplicate(
                $setup->getTable('tnw_salesforce_mapper'),
                $magento_attr
            );
        }

        // Customer Address to Account and Contact
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
            $attribute = $salesForceSetup->getAttribute(
                'customer_address',
                $magento_attr['magento_attribute_name']
            );
            $magento_attr['magento_entity_type'] = 'customer_address/shipping';
            $magento_attr['attribute_id'] = $attribute['attribute_id'];
            $setup->getConnection()->insertOnDuplicate(
                $setup->getTable('tnw_salesforce_mapper'),
                $magento_attr
            );
        }

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
            $attribute = $salesForceSetup->getAttribute(
                'customer_address',
                $magento_attr['magento_attribute_name']
            );
            $magento_attr['attribute_id'] = $attribute['attribute_id'];
            $magento_attr['magento_entity_type'] = 'customer_address/billing';
            $setup->getConnection()->insertOnDuplicate(
                $setup->getTable('tnw_salesforce_mapper'),
                $magento_attr
            );
        }

        // prepare default mapping for customer to account
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
            $attribute = $salesForceSetup->getAttribute(
                Customer::ENTITY,
                $magento_attr['magento_attribute_name']
            );
            $magento_attr['attribute_id'] = $attribute['attribute_id'];
            $magento_attr['magento_entity_type'] = 'customer';
            $setup->getConnection()->insertOnDuplicate(
                $setup->getTable('tnw_salesforce_mapper'),
                $magento_attr
            );
        }

        // Customer Address to Account
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
            $attribute = $salesForceSetup->getAttribute(
                'customer_address',
                $magento_attr['magento_attribute_name']
            );
            $magento_attr['magento_entity_type'] = 'customer_address/shipping';
            $magento_attr['attribute_id'] = $attribute['attribute_id'];
            $setup->getConnection()->insertOnDuplicate(
                $setup->getTable('tnw_salesforce_mapper'),
                $magento_attr
            );
        }

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
//            [
//                'magento_attribute_name' => 'region_id',
//                'salesforce_attribute_name' => 'BillingState',
//                'attribute_type' => 'string',
//                'object_type' => 'Account'
//            ],
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
            $attribute = $salesForceSetup->getAttribute(
                'customer_address',
                $magento_attr['magento_attribute_name']
            );
            $magento_attr['attribute_id'] = $attribute['attribute_id'];
            $magento_attr['magento_entity_type'] = 'customer_address/billing';
            $setup->getConnection()->insertOnDuplicate(
                $setup->getTable('tnw_salesforce_mapper'),
                $magento_attr
            );
        }

        //0.1.0.3
        $customerToContact = [
            'magento_attribute_name' => 'Id',
            'salesforce_attribute_name' => sprintf(
                '%s%s',
                Map::SFORCE_BASIC_PREFIX,
                Map::SFORCE_MAGENTO_ID
            ),
            'attribute_type' => 'string',
            'object_type' => 'Contact',
            'magento_entity_type' => 'customer',
            'is_default' => 1
        ];

        $this->_moduleDataSetup->getConnection()->insertOnDuplicate(
            $this->_moduleDataSetup->getTable('tnw_salesforce_mapper'),
            $customerToContact
        );

        $customerToAccount = [
            'magento_attribute_name' => 'Id',
            'salesforce_attribute_name' => sprintf(
                '%s%s',
                Map::SFORCE_BASIC_PREFIX,
                Map::SFORCE_MAGENTO_ID
            ),
            'attribute_type' => 'string',
            'object_type' => 'Account',
            'magento_entity_type' => 'customer',
            'is_default' => 1
        ];

        $this->_moduleDataSetup->getConnection()->insertOnDuplicate(
            $this->_moduleDataSetup->getTable('tnw_salesforce_mapper'),
            $customerToAccount
        );

        //2.0.2
        $this->updateAttributeTypes($this->_moduleDataSetup);
        //2.0.2
        $this->versionTwoZeroTwo($salesForceSetup);
        //2.0.3
        $this->versionTwoZeroThree();
        //2.1.1
        $this->versionTwoOneOne();
        //2.1.8
        $this->versionTwoOneEight();
        //2.1.11
        $this->versionTwoOneEleven();
        //2.3.11
        $this->versionTwoThreeEleven();
        //2.4.8
        $this->versionTwoFourEight();

        $this->_moduleDataSetup->endSetup();
    }

    /**
     * VersionTwoZeroTwo
     *
     * @param SalesforceSetup $eavSetup
     * @return void
     */
    protected function versionTwoZeroTwo(SalesforceSetup $eavSetup)
    {
        $eavSetup->updateAttribute(
            Customer::ENTITY,
            'sync_status',
            'frontend_input',
            'select'
        );
        $eavSetup->updateAttribute(
            Customer::ENTITY,
            'sync_status',
            'attribute_code',
            'sforce_sync_status'
        );
    }

    /**
     * VersionTwoZeroThree
     *
     * @return void
     */
    protected function versionTwoZeroThree()
    {
        // Delete custom grid settings to change grid columns order
        $this->_moduleDataSetup->getConnection()->delete(
            $this->_moduleDataSetup->getTable('ui_bookmark'),
            [
                "namespace = ?" => 'customer_listing',
            ]
        );
    }

    /**
     * VersionTwoOneOne
     *
     * @return void
     */
    protected function versionTwoOneOne()
    {
        $salesForceSetup = $this->_salesForceSetupFactory->create(
            [
                'setup' => $this->_moduleDataSetup
            ]
        );
        $this->updateAttributes(
            [
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
            ],
            $salesForceSetup
        );
    }

    /**
     * VersionTwoOneEight
     *
     * @return void
     */
    protected function versionTwoOneEight()
    {
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

        $this->_moduleDataSetup->getConnection()
            ->insertOnDuplicate(
                $this->_moduleDataSetup->getTable('tnw_salesforce_mapper'),
                $defaultMap
            );
    }

    /**
     * VersionTwoThreeEleven
     *
     * @return void
     */
    protected function versionTwoThreeEleven()
    {
        $this->_moduleDataSetup->getConnection()->insertOnDuplicate(
            $this->_moduleDataSetup->getTable('core_config_data'),
            [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => 'tnw_salesforce/survey/start_date',
                'value' => date_create()->modify('+7 day')->getTimestamp()
            ]
        );
    }

    /**
     * VersionTwoOneEleven
     *
     * @return void
     */
    protected function versionTwoOneEleven()
    {
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

        $this->_moduleDataSetup->getConnection()
            ->insertOnDuplicate(
                $this->_moduleDataSetup->getTable('tnw_salesforce_mapper'),
                $defaultMap
            );
    }

    /**
     * VersionTwoFourEight
     *
     * @return void
     */
    protected function versionTwoFourEight()
    {
        $salesForceSetup = $this->_salesForceSetupFactory->create(
            [
                'setup' => $this->_moduleDataSetup
            ]
        );
        $connection = $this->_moduleDataSetup->getConnection();

        // Website
        $select = $connection->select()
            ->from(
                $this->_moduleDataSetup->getTable('store_website'),
                [
                    'entity_id' => 'website_id',
                    'object_id' => 'salesforce_id',
                    'magento_type' => new Zend_Db_Expr('"Website"'),
                    'salesforce_type' => new Zend_Db_Expr('"tnw_mage_basic__Magento_Website__c"'),
                    'website_id' => new Zend_Db_Expr('0'),
                ]
            );

        $query = $connection->insertFromSelect(
            $select,
            $this->_moduleDataSetup->getTable('tnw_salesforce_objects'),
            [
                'entity_id',
                'object_id',
                'magento_type',
                'salesforce_type',
                'website_id'
            ],
            AdapterInterface::INSERT_ON_DUPLICATE
        );

        $connection->query($query);

        // Account
        $select = $connection->select()
            ->from(
                $salesForceSetup->getAttributeTable(
                    Customer::ENTITY,
                    'sforce_account_id'
                ),
                [
                    'entity_id' => 'entity_id',
                    'object_id' => 'value',
                    'magento_type' => new Zend_Db_Expr('"Customer"'),
                    'salesforce_type' => new Zend_Db_Expr('"Account"'),
                    'website_id' => new Zend_Db_Expr('0'),
                ]
            )
            ->where(
                'attribute_id = ?',
                $salesForceSetup->getAttribute(
                    Customer::ENTITY,
                    'sforce_account_id',
                    'attribute_id'
                )
            );

        $query = $connection->insertFromSelect(
            $select,
            $this->_moduleDataSetup->getTable('tnw_salesforce_objects'),
            [
                'entity_id',
                'object_id',
                'magento_type',
                'salesforce_type',
                'website_id'
            ],
            AdapterInterface::INSERT_ON_DUPLICATE
        );

        $connection->query($query);

        // Contact
        $select = $connection->select()
            ->from(
                $salesForceSetup->getAttributeTable(Customer::ENTITY, 'sforce_id'),
                [
                    'entity_id' => 'entity_id',
                    'object_id' => 'value',
                    'magento_type' => new Zend_Db_Expr('"Customer"'),
                    'salesforce_type' => new Zend_Db_Expr('"Contact"'),
                    'website_id' => new Zend_Db_Expr('0'),
                ]
            )
            ->where(
                'attribute_id = ?',
                $salesForceSetup->getAttribute(
                    Customer::ENTITY,
                    'sforce_id',
                    'attribute_id'
                )
            );

        $query = $connection->insertFromSelect(
            $select,
            $this->_moduleDataSetup->getTable('tnw_salesforce_objects'),
            [
                'entity_id',
                'object_id',
                'magento_type',
                'salesforce_type',
                'website_id'
            ],
            AdapterInterface::INSERT_ON_DUPLICATE
        );

        $connection->query($query);
    }

    /**
     * Update attributes
     * @param array $entityAttributes
     * @param SalesforceSetup $salesForceSetup
     * @return void
     */
    protected function updateAttributes(
        array $entityAttributes,
        SalesforceSetup $salesForceSetup
    ) {
        foreach ($entityAttributes as $entityType => $attributes) {
            foreach ($attributes as $attributeCode => $attributeData) {
                foreach ($attributeData as $attributeField => $value) {
                    $salesForceSetup->updateAttribute(
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
     * UpdateAttributeTypes
     * @param ModuleDataSetupInterface $setup
     */
    protected function updateAttributeTypes(ModuleDataSetupInterface $setup)
    {
        $eavSetup = $this->_salesForceSetupFactory->create(
            ['setup' => $this->_moduleDataSetup]
        );

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
                case 'customer':
                    $attribute = $eavSetup->getAttribute(
                        Customer::ENTITY,
                        $customerToContactAndAccount['magento_attribute_name']
                    );
                    break;
                case 'customer_address/billing':
                case 'customer_address/shipping':
                    $attribute = $eavSetup->getAttribute(
                        AttributeProvider::ENTITY,
                        $customerToContactAndAccount['magento_attribute_name']
                    );
                    break;
            }

            if (isset($attribute['backend_type'])) {
                $customerToContactAndAccount['attribute_type']
                    = $attribute['backend_type'];
            }

            $setup->getConnection()
                ->insertOnDuplicate(
                    $setup->getTable('tnw_salesforce_mapper'),
                    $customerToContactAndAccount
                );
        }
    }

    public function revert()
    {
        // TODO: Implement revert() method.
    }
}
