<?php
namespace TNW\Salesforce\Test\Integration\Model\Customer;

/**
 * Class MapTest
 * @package TNW\Salesforce\Test\Integration\Model\Customer
 */
class MapTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \TNW\Salesforce\Model\Customer\Map
     */
    protected $model;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Magento\Customer\Api\Data\CustomerInterface
     */
    protected $customer;

    /**
     * @var \Magento\Store\Model\ResourceModel\Website
     */
    protected $websiteResourceModel;

    /**
     * @var \Magento\Store\Model\Website
     */
    protected $website;

    /**
     * @var string
     */
    protected $salesforceId = 'a0000000001AbcEFDH';

    /**
     * Test setup
     */
    public function setUp()
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

        $this->model = $this->objectManager->create('TNW\Salesforce\Model\Customer\Map');
        $this->websiteResourceModel = $this->objectManager->create('Magento\Store\Model\ResourceModel\Website');
        $this->customer = $this->objectManager->create('Magento\Customer\Api\Data\CustomerInterface');

        $websitesList = $this->objectManager->create('Magento\Store\Api\WebsiteRepositoryInterface')->getList();
        $this->website = array_shift($websitesList);
    }

    /**
     * Test for \TNW\Salesforce\Model\Customer\Map::getContactTransferObjectFromCustomer
     *
     * @param $addressesData
     * @param $expectedResult
     *
     * @dataProvider testGetContactTransferObjectFromCustomerDataProvider
     */
    public function testGetContactTransferObjectFromCustomer($addressesData, $expectedResult)
    {
        $this->website->setSalesforceId($this->salesforceId);
        $this->websiteResourceModel->save($this->website);

        $this->customer
            ->setId(1)
            ->setWebsiteId($this->website->getId());

        foreach ($addressesData as $addressData) {
            $address = $this->objectManager->create('Magento\Customer\Api\Data\AddressInterface');
            $address
                ->setStreet($addressData['Street'])
                ->setCity($addressData['City'])
                ->setCountryId($addressData['CountryId'])
                ->setPostcode($addressData['Postcode'])
                ->setTelephone($addressData['Telephone'])
                ->setId($addressData['Id']);
            $this->customer->setAddresses([$address]);

            if ($addressData['Type'] == 'Billing') {
                $this->customer->setDefaultBilling($addressData['Id']);
            } elseif ($addressData['Type'] == 'Shipping') {
                $this->customer->setDefaultShipping($addressData['Id']);
            }
        }

        $this->assertEquals(
            (object)$expectedResult,
            $this->model->getContactTransferObjectFromCustomer($this->customer)
        );
    }

    /**
     * Data provider for testGetContactTransferObjectFromCustomer
     *
     * @return array
     */
    public function testGetContactTransferObjectFromCustomerDataProvider()
    {
        return [
            [
                [
                    [
                        'Street' => ['Test Street 1'],
                        'City' => 'Test City 1',
                        'CountryId' => 'US',
                        'Postcode' => '00001',
                        'Telephone' => '0000000001',
                        'Id' => 1,
                        'Type' => 'Billing'
                    ],
                    [
                        'Street' => ['Test Street 2'],
                        'City' => 'Test City 2',
                        'CountryId' => 'US',
                        'Postcode' => '00002',
                        'Telephone' => '0000000002',
                        'Id' => 2,
                        'Type' => 'Shipping'
                    ]
                ],
                [
                    'MailingStreet' => 'Test Street 2',
                    'MailingCity' => 'Test City 2',
                    'MailingPostalCode' => '00002',
                    'MailingCountry' => 'US',
                    'Phone' => '0000000002',
                    'tnw_mage_basic__Magento_ID__c' => 1,
                    'tnw_mage_basic__Magento_Website__c' => $this->salesforceId
                ]
            ],
            [
                [
                    [
                        'Street' => ['Test Street 1'],
                        'City' => 'Test City 1',
                        'CountryId' => 'US',
                        'Postcode' => '00001',
                        'Telephone' => '0000000001',
                        'Id' => 1,
                        'Type' => 'Billing'
                    ]
                ],
                [
                    'OtherStreet' => 'Test Street 1',
                    'OtherCity' => 'Test City 1',
                    'OtherPostalCode' => '00001',
                    'OtherCountry' => 'US',
                    'OtherPhone' => '0000000001',
                    'tnw_mage_basic__Magento_ID__c' => 1,
                    'tnw_mage_basic__Magento_Website__c' => $this->salesforceId
                ]
            ],
            [
                [
                    [
                        'Street' => ['Test Street 2'],
                        'City' => 'Test City 2',
                        'CountryId' => 'US',
                        'Postcode' => '00002',
                        'Telephone' => '0000000002',
                        'Id' => 2,
                        'Type' => 'Shipping'
                    ]
                ],
                [
                    'MailingStreet' => 'Test Street 2',
                    'MailingCity' => 'Test City 2',
                    'MailingPostalCode' => '00002',
                    'MailingCountry' => 'US',
                    'Phone' => '0000000002',
                    'tnw_mage_basic__Magento_ID__c' => 1,
                    'tnw_mage_basic__Magento_Website__c' => $this->salesforceId
                ]
            ]
        ];
    }

    /**
     * Test tear down
     */
    public function tearDown()
    {
        $this->model = null;
        $this->objectManager = null;
        $this->customer = null;

        if ($this->website->getSalesforceId()) {
            $this->website->setSalesforceId(null);
            $this->websiteResourceModel->save($this->website);
        }

        $this->website = null;
        $this->websiteResourceModel = null;
    }
}
