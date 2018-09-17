<?php
namespace TNW\Salesforce\Test\Unit\Client;

/**
 * Class CustomerTest
 * @package TNW\Salesforce\Test\Unit\Client
 */
class CustomerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \TNW\Salesforce\Model\Config mock
     */
    protected $salesforceConfigMock;

    /**
     * @var \Magento\Framework\App\Cache\Type\Collection mock
     */
    protected $cacheCollectiongMock;

    /**
     * @var \Magento\Customer\Model\ResourceModel\CustomerRepository mock
     */
    protected $customerRepositoryMock;

    /**
     * @var \Magento\Framework\ObjectManagerInterface mock
     */
    protected $objectManagerMock;

    /**
     * @var \TNW\Salesforce\Model\Customer\Map mock
     */
    protected $mapMock;

    /**
     * @var \TNW\Salesforce\Model\Customer\CustomAttribute mock
     */
    protected $customAttributeMock;

    /**
     * @var \TNW\Salesforce\Client\Customer
     */
    protected $client;

    /**
     * Test setup
     */
    protected function setUp()
    {
        $this->customerRepositoryMock = $this->getMock(
            'Magento\Customer\Model\ResourceModel\CustomerRepository',
            [],
            [],
            '',
            false
        );

        $this->cacheCollectiongMock = $this->getMock(
            'Magento\Framework\App\Cache\Type\Collection',
            [],
            [],
            '',
            false
        );

        $this->objectManagerMock = $this->getMockForAbstractClass(
            'Magento\Framework\ObjectManagerInterface',
            [],
            '',
            false
        );

        $this->mapMock = $this->getMock(
            'TNW\Salesforce\Model\Customer\Map',
            [],
            [],
            '',
            false
        );

        $this->customAttributeMock = $this->getMock(
            'TNW\Salesforce\Model\Customer\CustomAttribute',
            [],
            [],
            '',
            false
        );

        $this->salesforceConfigMock = $this->getMock(
            'TNW\Salesforce\Model\Config',
            [],
            [],
            '',
            false
        );

        $this->client = new \TNW\Salesforce\Client\Customer(
            $this->customerRepositoryMock,
            $this->cacheCollectiongMock,
            $this->objectManagerMock,
            $this->mapMock,
            $this->customAttributeMock,
            $this->salesforceConfigMock
        );
    }

    /**
     * Test for \TNW\Salesforce\Client\Customer::getClientStatus
     * @param $configValue
     * @param $expectedValue
     *
     * @dataProvider getClientStatusDataProvider
     */
    public function testGetClientStatus($configValue, $expectedValue)
    {
        $this->salesforceConfigMock->expects(
            $this->any()
        )->method(
            'getCustomerStatus'
        )->will(
            $this->returnValue($configValue)
        );

        $this->assertSame($expectedValue, $this->client->getClientStatus());
    }

    /**
     * Data provider for testGetClientStatus
     * @return array
     */
    public function getClientStatusDataProvider()
    {
        return [
            'Config set to 1'      => ['1', true],
            'Config set to 0'      => ['0', false],
            'Config value not set' => [null, false]
        ];
    }
}
