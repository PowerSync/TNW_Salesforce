<?php
declare(strict_types=1);

namespace TNW\Salesforce\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{
    /**
     * EAV setup factory
     *
     * @var EavSetup
     */
    private $eavSetup;

    /**
     * Init
     *
     * @param EavSetup $eavSetup
     */
    public function __construct(EavSetup $eavSetup)
    {
        $this->eavSetup = $eavSetup;
    }

    /**
     * {@inheritdoc}
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /** @var EavSetup $eavSetup */

        $this->eavSetup->addAttribute(
            \Magento\Customer\Model\Customer::ENTITY,
            'sforce_id',
            [
                'type' => 'varchar',
                'required' => false,
                'sort_order' => 1,
                'visible' => true,
                'system' => false,
                'group' => 'Account Information',
                'default' => null,
                'label' => 'Salesforce Contact Id'
            ]
        );

        $this->eavSetup->addAttribute(
            \Magento\Customer\Model\Customer::ENTITY,
            'sforce_account_id',
            [
                'type' => 'varchar',
                'required' => false,
                'sort_order' => 2,
                'visible' => true,
                'system' => false,
                'group' => 'Account Information',
                'default' => null,
                'label' => 'Salesforce Account Id'
            ]
        );

        $this->eavSetup->addAttribute(
            \Magento\Customer\Model\Customer::ENTITY,
            'sync_status',
            [
                'type' => 'int',
                'required' => false,
                'sort_order' => 3,
                'visible' => true,
                'system' => false,
                'group' => 'Account Information',
                'default' => 0,
                'label' => 'Sync Status'
            ]
        );


        //prepare default mapping for customer to contact
        $customerToContact = [
            [   'magento_attribute_name'=>'email',
                'salesforce_attribute_name'=>'Email',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ],
            [   'magento_attribute_name'=>'firstname',
                'salesforce_attribute_name'=>'FirstName',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ],
            [   'magento_attribute_name'=>'lastname',
                'salesforce_attribute_name'=>'LastName',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ],
            [   'magento_attribute_name'=>'dob',
                'salesforce_attribute_name'=>'Birthdate',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ]

        ];

        foreach($customerToContact as $magento_attr){

            $attribute = $this->eavSetup->getAttribute(\Magento\Customer\Model\Customer::ENTITY,
                $magento_attr['magento_attribute_name']
            );
            $magento_attr['attribute_id'] = $attribute['attribute_id'];
            $magento_attr['magento_entity_type'] = 'customer';
            $setup->getConnection()->insert($setup->getTable('tnw_salesforce_mapper'), $magento_attr);
        }

        // Customer Address to Account and Contact
        $customerShippingToContact = [
            [   'magento_attribute_name'=>'street',
                'salesforce_attribute_name'=>'MailingStreet',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ],
            [   'magento_attribute_name'=>'city',
                'salesforce_attribute_name'=>'MailingCity',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ],
            [   'magento_attribute_name'=>'region',
                'salesforce_attribute_name'=>'MailingState',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ],
            [   'magento_attribute_name'=>'postcode',
                'salesforce_attribute_name'=>'MailingPostalCode',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ],
            [   'magento_attribute_name'=>'country_id',
                'salesforce_attribute_name'=>'MailingCountry',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ],
            [   'magento_attribute_name'=>'telephone',
                'salesforce_attribute_name'=>'Phone',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ]
        ];

        foreach($customerShippingToContact as $magento_attr){

            $attribute = $this->eavSetup->getAttribute('customer_address',
                $magento_attr['magento_attribute_name']
            );
            $magento_attr['magento_entity_type'] = 'customer_address/shipping';
            $magento_attr['attribute_id'] = $attribute['attribute_id'];
            $setup->getConnection()->insert($setup->getTable('tnw_salesforce_mapper'), $magento_attr);
        }

        /** @var  $customerBillingToContact */
        $customerBillingToContact = [
            [   'magento_attribute_name'=>'company',
                'salesforce_attribute_name'=>'Name',
                'attribute_type' => 'string',
                'object_type' => 'Account'
            ],
            [   'magento_attribute_name'=>'street',
                'salesforce_attribute_name'=>'OtherStreet',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ],
            [   'magento_attribute_name'=>'city',
                'salesforce_attribute_name'=>'OtherCity',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ],
            [   'magento_attribute_name'=>'region',
                'salesforce_attribute_name'=>'OtherState',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ],
            [   'magento_attribute_name'=>'postcode',
                'salesforce_attribute_name'=>'OtherPostalCode',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ],
            [   'magento_attribute_name'=>'country_id',
                'salesforce_attribute_name'=>'OtherCountry',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ],
            [   'magento_attribute_name'=>'telephone',
                'salesforce_attribute_name'=>'OtherPhone',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ]
        ];

        foreach($customerBillingToContact as $magento_attr){

            $attribute = $this->eavSetup->getAttribute('customer_address',
                $magento_attr['magento_attribute_name']
            );
            $magento_attr['attribute_id'] = $attribute['attribute_id'];
            $magento_attr['magento_entity_type'] = 'customer_address/billing';
            $setup->getConnection()->insert($setup->getTable('tnw_salesforce_mapper'), $magento_attr);
        }

    }
}
