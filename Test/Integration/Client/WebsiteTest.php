<?php
namespace TNW\Test\Integration\Client;

/**
 * Class WebsiteTest
 * @package TNW\Test\Integration\Client
 */
class WebsiteTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \TNW\Salesforce\Client\Website
     */
    protected $client;

    /**
     * @var \Magento\Store\Model\Website[]
     */
    protected $websites;

    /**
     * @var \Tnw\SoapClient\Result\UpsertResult[]
     */
    protected $upsertResult;

    /**
     * @var \Tnw\SoapClient\Client mock
     */
    protected $soapClient;

    /**
     * Test setup
     */
    public function setUp()
    {
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $websitesList = $objectManager->create('Magento\Store\Api\WebsiteRepositoryInterface')->getList();
        $this->websites['website'] = array_shift($websitesList);

        $this->upsertResult = $this->getMock(
            'Tnw\SoapClient\Result\UpsertResult',
            ['isSuccess', 'getId', 'getErrors'],
            [],
            '',
            false
        );

        $this->soapClient = $this->getMock(
            'Tnw\SoapClient\Client',
            ['upsert'],
            [],
            '',
            false
        );

        $resourceSfWebsite = $this->getMock(
            'TNW\Salesforce\Model\ResourceModel\Website',
            ['saveSalesforceId'],
            [],
            '',
            false
        );

        $this->client = $this->getMock(
            'TNW\Salesforce\Client\Website',
            ['getClient'],
            [
                $objectManager->create('TNW\Salesforce\Model\Config'),
                $objectManager->create('Magento\Framework\App\Cache\Type\Collection'),
                $objectManager->create('Magento\Framework\ObjectManager\ObjectManager'),
                $resourceSfWebsite,
                $objectManager->create('Magento\Framework\Message\ManagerInterface')
            ],
            '',
            true
        );
    }

    /**
     * Test for \TNW\Salesforce\Client\Website::syncWebsites
     *
     * @param $upsertResultData
     * @param $expectedResult
     *
     * @dataProvider testSyncWebsitesDataProvider
     */
    public function testSyncWebsites($upsertResultData, $expectedResult)
    {
        $this->upsertResult
            ->expects($this->any())
            ->method('isSuccess')
            ->will($this->returnValue($upsertResultData['success']));
        $this->upsertResult
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($upsertResultData['id']));
        $this->upsertResult
            ->expects($this->any())
            ->method('getErrors')
            ->will($this->returnValue($upsertResultData['errors']));

        $this->soapClient
            ->expects($this->any())
            ->method('upsert')
            ->will($this->returnValue([$this->upsertResult]));

        $this->client
            ->expects($this->any())
            ->method('getClient')
            ->will($this->returnValue($this->soapClient));

        $this->assertEquals($expectedResult, $this->client->syncWebsites($this->websites));
    }

    /**
     * Data provider for testSyncWebsites
     *
     * @return array
     */
    public function testSyncWebsitesDataProvider()
    {
        return [
            [
                ['success' => true, 'id' => '1', 'errors' => []],
                []
            ],
            [
                ['success' => false, 'id' => '1', 'errors' => ['Test error message']],
                ['0' => ['Test error message']]]
        ];
    }

    /**
     * Test tear down
     */
    public function tearDown()
    {
        $this->client       = null;
        $this->websites     = null;
        $this->upsertResult = null;
        $this->soapClient   = null;
    }
}
