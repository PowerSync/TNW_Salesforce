<?php
namespace TNW\Salesforce\Test\Integration\Model\ResourceModel;

/**
 * Class WebsiteTest
 * @package TNW\Salesforce\Test\Integration\Model\ResourceModel
 */
class WebsiteTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \TNW\Salesforce\Model\ResourceModel\Website
     */
    protected $resourceModel;

    /**
     * @var \Magento\Store\Model\Website
     */
    protected $websiteObject;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var string
     */
    protected $websiteRepository = 'Magento\Store\Api\WebsiteRepositoryInterface';

    /**
     * Test setup
     */
    public function setUp()
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->resourceModel = $this->objectManager->create('TNW\Salesforce\Model\ResourceModel\Website');

        $websitesList = $this->objectManager->create($this->websiteRepository)->getList();
        $this->websiteObject = array_shift($websitesList);
    }

    /**
     * Test for \TNW\Salesforce\Model\ResourceModel\Website::saveSalesforceId
     *
     * @param $expectedResult
     *
     * @dataProvider testSaveSalesforceIdDataProvider
     */
    public function testSaveSalesforceId($expectedResult)
    {
        $this->websiteObject->setSalesforceId($expectedResult);
        $this->resourceModel->saveSalesforceId($this->websiteObject);

        $this->assertEquals(
            $expectedResult,
            $this->objectManager
                ->create($this->websiteRepository)
                ->getById($this->websiteObject->getId())
                ->getSalesforceId()
        );
    }

    /**
     * Data provider for testSaveSalesforceId
     *
     * @return array
     */
    public function testSaveSalesforceIdDataProvider()
    {
        return [
            ['a0000000001AbcEFDH']
        ];
    }

    /**
     * Test tear down
     */
    public function tearDown()
    {
        $this->resourceModel = null;
        $this->websiteObject = null;
        $this->objectManager = null;
    }
}
