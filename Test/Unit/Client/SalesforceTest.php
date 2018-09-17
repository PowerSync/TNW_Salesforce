<?php
namespace TNW\Salesforce\Test\Unit\Client;

/**
 * Class SalesforceTest
 * @package TNW\Salesforce\Test\Unit\Client
 */
class SalesforceTest extends \PHPUnit_Framework_TestCase
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
     * @var \TNW\Salesforce\Client\Salesforce
     */
    protected $client;

    /**
     * Test setup
     */
    protected function setUp()
    {
        $this->salesforceConfigMock = $this->getMock(
            'TNW\Salesforce\Model\Config',
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

        $this->client = new \TNW\Salesforce\Client\Salesforce(
            $this->salesforceConfigMock,
            $this->cacheCollectiongMock
        );
    }

    /**
     * Test for \TNW\Salesforce\Client\Salesforce::getClientStatus
     * @param string $configValue
     * @param bool $expectedValue
     *
     * @dataProvider getClientStatusDataProvider
     */
    public function testGetClientStatus($configValue, $expectedValue)
    {
        $this->salesforceConfigMock->expects(
            $this->any()
        )->method(
            'getSalesforceStatus'
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
