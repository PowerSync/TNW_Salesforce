<?php
namespace TNW\Salesforce\Test\Unit\Model\Customer;

/**
 * Class MapTest
 * @package TNW\Salesforce\Test\Unit\Model\Customer
 */
class MapTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \TNW\Salesforce\Model\Customer\Map
     */
    protected $model;

    /**
     * @var \TNW\Salesforce\Model\ResourceModel\Customer\Mapper\Repository mock
     */
    protected $customerRepositoryMapperMock;

    /**
     * @var \Magento\Framework\ObjectManagerInterface mock
     */
    protected $objectManagerMock;

    /**
     * Test setup
     */
    public function setUp()
    {
        $this->customerRepositoryMapperMock = $this->getMock(
            'TNW\Salesforce\Model\ResourceModel\Customer\Mapper\Repository',
            [],
            [],
            '',
            false
        );
        $this->objectManagerMock = $this->getMock(
            'Magento\Framework\ObjectManagerInterface',
            [],
            [],
            '',
            false
        );
        $this->model = new \TNW\Salesforce\Model\Customer\Map(
            $this->customerRepositoryMapperMock,
            $this->objectManagerMock
        );
    }

    /**
     * Test for TNW\Salesforce\Model\Customer\Map::getMapArray
     * @param $testData
     * @param $expectedValue
     *
     * @dataProvider getMapArrayDataProvider
     */
    public function testGetMapArray($testData, $expectedValue)
    {
        $salesforceCustomerMapperCollectionMock = $this->getMock(
            'TNW\Salesforce\Model\ResourceModel\Customer\Mapper\Collection',
            [],
            [],
            '',
            false
        );
        $collectionMockItems = [];
        foreach ($testData['items'] as $mapperData) {
            $mapper = $this->getMock(
                'TNW\Salesforce\Model\Customer\Mapper',
                [],
                [],
                '',
                false
            );
            $mapper->expects(
                $this->once()
            )->method(
                'getMagentoEntityType'
            )->will(
                $this->returnValue($mapperData['magentoEntityType'])
            );
            $mapper->expects(
                $this->once()
            )->method(
                'getSalesforceAttributeName'
            )->will(
                $this->returnValue($mapperData['salesforceAttributeName'])
            );
            $mapper->expects(
                $this->once()
            )->method(
                'getMagentoAttributeName'
            )->will(
                $this->returnValue($mapperData['magentoAttributeName'])
            );

            $collectionMockItems[] = $mapper;
        }

        $salesforceCustomerMapperCollectionMock->expects(
            $this->once()
        )->method(
            'getItems'
        )->will(
            $this->returnValue($collectionMockItems)
        );
        $this->customerRepositoryMapperMock->expects(
            $this->once()
        )->method(
            'getResultCollection'
        )->with(
            $testData['objectType']
        )->will(
            $this->returnValue($salesforceCustomerMapperCollectionMock)
        );
        $this->assertEquals($expectedValue, $this->model->getMapArray($testData['objectType']));
    }

    /**
     * DataProvider for testGetMapArray
     * @return array
     */
    public function getMapArrayDataProvider()
    {
        $expectedValue = [
            'entity_type_1' => ['sf_attribute_name_1' => 'magento_attribute_name_1'],
            'entity_type_2' => ['sf_attribute_name_2' => 'magento_attribute_name_2']
        ];
        $mapperItemsData = ['items' => [], 'objectType' => 'contact'];
        foreach ($expectedValue as $magentoEntityType => $attributeMapData) {
            $mapperItemData = [];
            $mapperItemData['magentoEntityType'] = $magentoEntityType;
            foreach ($attributeMapData as $sfAttributeName => $magentoAttributeName) {
                $mapperItemData['salesforceAttributeName'] = $sfAttributeName;
                $mapperItemData['magentoAttributeName'] = $magentoAttributeName;
            }
            $mapperItemsData['items'][] = $mapperItemData;
        }
        return [[$mapperItemsData, $expectedValue]];
    }

    /**
     * Test for \TNW\Salesforce\Model\Customer\Map::getAccountTransferObjectFromCustomer
     * @param $testData
     * @param $expectedValue
     *
     * @dataProvider getAccountTransferObjectFromCustomerDataProvider
     */
    public function testGetAccountTransferObjectFromCustomer($testData, $expectedValue)
    {
        /** @var $customerMock \Magento\Customer\Api\Data\CustomerInterface */
        $customerMock = $this->getMock(
            'Magento\Customer\Api\Data\CustomerInterface',
            [],
            [],
            '',
            false
        );
        /** @var $addressMock \Magento\Customer\Api\Data\AddressInterface */
        $addressMock = $this->getMock(
            'Magento\Customer\Api\Data\AddressInterface',
            [],
            [],
            '',
            false
        );
        /** @var $testModelMock \TNW\Salesforce\Model\Customer\Map */
        $testModelMock = $this->getMock(
            'TNW\Salesforce\Model\Customer\Map',
            [
                'getMapArray',
                'getDefaultBillingAddress',
                'getDefaultShippingAddress'
            ],
            [
                $this->customerRepositoryMapperMock,
                $this->objectManagerMock
            ],
            '',
            true
        );
        $dataProcessorMock = $this->getMock(
            'Magento\Framework\Reflection\DataObjectProcessor',
            [],
            [],
            '',
            false
        );
        $dataProcessorMock->expects(
            $this->any()
        )->method(
            'buildOutputDataArray'
        )->will(
            $this->returnValueMap([
                [$addressMock, '\Magento\Customer\Api\Data\AddressInterface', $testData['addressArray']],
                [$customerMock, '\Magento\Customer\Api\Data\CustomerInterface', $testData['customerArray']]
            ])
        );
        $this->objectManagerMock->expects(
            $this->any()
        )->method(
            'get'
        )->will(
            $this->returnValue($dataProcessorMock)
        );
        $testModelMock->expects(
            $this->once()
        )->method(
            'getMapArray'
        )->with(
            \TNW\Salesforce\Model\Customer\Mapper::OBJECT_TYPE_ACCOUNT
        )->will(
            $this->returnValue($testData['mapArray'])
        );
        $testModelMock->expects(
            $this->any()
        )->method(
            'getDefaultBillingAddress'
        )->with(
            $customerMock
        )->will(
            $this->returnValue($testData['billingAddress'] ? $addressMock : null)
        );
        $testModelMock->expects(
            $this->any()
        )->method(
            'getDefaultShippingAddress'
        )->with(
            $customerMock
        )->will(
            $this->returnValue($testData['shippingAddress'] ? $addressMock : null)
        );

        $this->assertEquals($expectedValue, $testModelMock->getAccountTransferObjectFromCustomer($customerMock));
    }

    /**
     * DataProvider for testGetAccountTransferObjectFromCustomer
     * @return array
     */
    public function getAccountTransferObjectFromCustomerDataProvider()
    {
        return [
            'With default billing address, company name and sForceId' => [
                [
                    'customerArray' => ['sforce_account_id' => 'sforce_account_id'],
                    'mapArray' => ['customer_address/billing' => [
                        'sfForceAttributeName' => 'magentoAddressAttributeName',
                        'Name' => 'billingAddressCompany'
                    ]],
                    'billingAddress' => 'defaultBillingAddress',
                    'shippingAddress' => 'defaultShippingAddress',
                    'addressArray' => [
                        'magentoAddressAttributeName' => 'mappedAddressAttributeValue',
                        'billingAddressCompany' => 'billing_address_company'
                    ]
                ],
                (object) [
                    'Id' => 'sforce_account_id',
                    'Name' => 'billing_address_company',
                    'sfForceAttributeName' => 'mappedAddressAttributeValue'
                ]
            ],
            'With default shipping address, no company name and sForceId' => [
                [
                    'customerArray' => [
                        'sforce_account_id' => 'sforce_account_id',
                        'firstname' => 'firstname',
                        'lastname' => 'lastname'
                    ],
                    'mapArray' => ['customer_address/billing' => [
                        'sfForceAttributeName' => 'magentoAddressAttributeName',
                        'Name' => 'billingAddressCompany'
                    ]],
                    'billingAddress' => false,
                    'shippingAddress' => 'defaultShippingAddress',
                    'addressArray' => [
                        'magentoAddressAttributeName' => 'mappedAddressAttributeValue'
                    ]
                ],
                (object) [
                    'Id' => 'sforce_account_id',
                    'Name' => 'firstname lastname',
                    'sfForceAttributeName' => 'mappedAddressAttributeValue'
                ]
            ],
            'With no address and no sForceId' => [
                [
                    'customerArray' => [
                        'firstname' => 'firstname',
                        'lastname' => 'lastname'
                    ],
                    'mapArray' => ['customer_address/billing' => [
                        'sfForceAttributeName' => 'magentoAddressAttributeName',
                        'Name' => 'billingAddressCompany'
                    ]],
                    'billingAddress' => false,
                    'shippingAddress' => false,
                    'addressArray' => []
                ],
                (object) [
                    'Name' => 'firstname lastname'
                ]
            ]
        ];
    }

    /**
     * Test for \TNW\Salesforce\Model\Customer\Map::getDefaultShippingAddress
     * @param $testData
     * @param $expectedValue
     *
     * @dataProvider getDefaultAddressDataProvider
     */
    public function testGetDefaultShippingAddress($testData, $expectedValue)
    {
        /** @var $customerMock \Magento\Customer\Api\Data\CustomerInterface */
        $customerMock = $this->getMock(
            'Magento\Customer\Api\Data\CustomerInterface',
            [],
            [],
            '',
            false
        );
        /** @var $addressMock \Magento\Customer\Api\Data\AddressInterface */
        $addressMock = $this->getMock(
            'Magento\Customer\Api\Data\AddressInterface',
            [],
            [],
            '',
            false
        );

        $addressMock->expects(
            $this->any()
        )->method(
            'getId'
        )->will(
            $this->returnValue(array_key_exists('address_id', $testData) ? $testData['address_id'] : null)
        );
        $addressMock->expects(
            $this->any()
        )->method(
            'isDefaultShipping'
        )->will(
            $this->returnValue(array_key_exists('isDefault', $testData) ? $testData['isDefault'] : null)
        );
        $customerMock->expects(
            $this->once()
        )->method(
            'getAddresses'
        )->will(
            $this->returnValue(array_key_exists('address_id', $testData) ? [$addressMock] : [])
        );
        $customerMock->expects(
            $this->once()
        )->method(
            'getDefaultShipping'
        )->will(
            $this->returnValue(
                (array_key_exists('address_id', $testData) && array_key_exists('isDefault', $testData))
                && (array_key_exists('isDefault', $testData) ? $testData['isDefault'] : null)
                    ? $testData['address_id']
                    : null
            )
        );
        $expectedValue = $expectedValue ? $addressMock : $expectedValue;

        $this->assertEquals($expectedValue, $this->model->getDefaultShippingAddress($customerMock));
    }

    /**
     * Test for \TNW\Salesforce\Model\Customer\Map::getDefaultBillingAddress
     * @param $testData
     * @param $expectedValue
     *
     * @dataProvider getDefaultAddressDataProvider
     */
    public function testGetDefaultBillingAddress($testData, $expectedValue)
    {
        /** @var $customerMock \Magento\Customer\Api\Data\CustomerInterface */
        $customerMock = $this->getMock(
            'Magento\Customer\Api\Data\CustomerInterface',
            [],
            [],
            '',
            false
        );
        /** @var $addressMock \Magento\Customer\Api\Data\AddressInterface */
        $addressMock = $this->getMock(
            'Magento\Customer\Api\Data\AddressInterface',
            [],
            [],
            '',
            false
        );

        $addressMock->expects(
            $this->any()
        )->method(
            'getId'
        )->will(
            $this->returnValue(array_key_exists('address_id', $testData) ? $testData['address_id'] : null)
        );
        $addressMock->expects(
            $this->any()
        )->method(
            'isDefaultBilling'
        )->will(
            $this->returnValue(array_key_exists('isDefault', $testData) ? $testData['isDefault'] : null)
        );
        $customerMock->expects(
            $this->once()
        )->method(
            'getAddresses'
        )->will(
            $this->returnValue(array_key_exists('address_id', $testData) ? [$addressMock] : [])
        );
        $customerMock->expects(
            $this->once()
        )->method(
            'getDefaultBilling'
        )->will(
            $this->returnValue(
                (array_key_exists('address_id', $testData) && array_key_exists('isDefault', $testData))
                && (array_key_exists('isDefault', $testData) ? $testData['isDefault'] : null)
                    ? $testData['address_id']
                    : null
            )
        );
        $expectedValue = $expectedValue ? $addressMock : $expectedValue;

        $this->assertEquals($expectedValue, $this->model->getDefaultBillingAddress($customerMock));
    }

    /**
     * @return array
     */
    public function getDefaultAddressDataProvider()
    {
        return [
            'Customer Have no default address' => [
                [
                    'address_id' => 'someAddressId',
                    'isDefault' => false
                ],
                null
            ],
            'Customer Have default address' => [
                [
                    'address_id' => 'someAddressId',
                    'isDefault' => true
                ],
                'addressMock'
            ],
            'Customer Have no addresses' => [
                [],
                null
            ]
        ];
    }

    /**
     * Test tear down
     */
    public function tearDown()
    {
        $this->customerRepositoryMapperMock = null;
        $this->objectManagerMock            = null;
        $this->model                        = null;
    }
}
