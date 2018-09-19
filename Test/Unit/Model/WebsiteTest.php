<?php
namespace TNW\Salesforce\Test\Unit\Model;

/**
 * Class WebsiteTest
 * @package TNW\Salesforce\Test\Unit\Model
 */
class WebsiteTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Store\Model\Website mock
     */
    protected $websiteModelMock;

    /**
     * @var \Magento\Store\Model\WebsiteRepository mock
     */
    protected $websiteRepositoryMock;

    /**
     * @var \TNW\Salesforce\Client\Website mock
     */
    protected $clientWebsiteMock;

    /**
     * @var \TNW\Salesforce\Model\Logger mock
     */
    protected $loggerModelMock;

    /**
     * @var \Tnw\SoapClient\Result\Error mock
     */
    protected $resultErrorMock;

    /**
     * @var \TNW\Salesforce\Model\Website
     */
    protected $model;

    /**
     * Test setup
     */
    public function setUp()
    {
        $this->websiteModelMock = $this->getMock(
            'Magento\Store\Model\Website',
            ['getSalesforceId'],
            [],
            '',
            false
        );

        $this->websiteRepositoryMock = $this->getMock(
            'Magento\Store\Model\WebsiteRepository',
            [],
            [],
            '',
            false
        );

        $this->clientWebsiteMock = $this->getMock(
            'TNW\Salesforce\Client\Website',
            [],
            [],
            '',
            false
        );

        $this->loggerModelMock = $this->getMock(
            'TNW\Salesforce\Model\Logger',
            [],
            [],
            '',
            false
        );

        $this->resultErrorMock = $this->getMock(
            'Tnw\SoapClient\Result\Error',
            [],
            [],
            '',
            false
        );

        $this->model = new \TNW\Salesforce\Model\Website(
            $this->clientWebsiteMock,
            $this->websiteRepositoryMock,
            $this->loggerModelMock
        );
    }

    /**
     * Test for \TNW\Salesforce\Model\Website::syncWebsites
     *
     * @dataProvider syncWebsitesDataProvider
     */
    public function testSyncWebsites($websiteIds, $upsertError, $salesforceId, $expectedResult)
    {
        $this->websiteModelMock->expects(
            $this->any()
        )->method(
            'getSalesforceId'
        )->will(
            $this->returnValue($salesforceId)
        );

        $this->websiteRepositoryMock->expects(
            $this->any()
        )->method(
            'getById'
        )->will(
            $this->returnValue($this->websiteModelMock)
        );

        $this->resultErrorMock->expects(
            $this->any()
        )->method(
            'getMessage'
        )->will(
            $this->returnValue($upsertError['message'])
        );

        $syncErrors = [
            $upsertError['id'] => [$this->resultErrorMock]
        ];

        $this->clientWebsiteMock->expects(
            $this->any()
        )->method(
            'syncWebsites'
        )->will(
            $this->returnValue($syncErrors)
        );

        $actualResult = $this->model->syncWebsites($websiteIds);

        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * Data provider for testSyncWebsites
     *
     * @return array
     */
    public function syncWebsitesDataProvider()
    {
        $websitesIds = ['1', '2'];

        $upsertError = [
            'id'      => '2',
            'message' => 'Test Error Message'];

        $salesforceId = 'a0000000001AbcEFDH';

        $expectedResult = [
            [
                'id'       => '1',
                'status'   => 'synced',
                'errorMsg' => ' - '
            ],
            [
                'id'       => '2',
                'status'   => 'unsynced',
                'errorMsg' => ' - Test Error Message;'
            ],
        ];

        return [
            [$websitesIds, $upsertError, $salesforceId, $expectedResult],
        ];
    }

    /**
     * Test tear down
     */
    public function tearDown()
    {
        $this->websiteModelMock      = null;
        $this->websiteRepositoryMock = null;
        $this->clientWebsiteMock     = null;
        $this->loggerModelMock       = null;
        $this->resultErrorMock       = null;
        $this->model                 = null;

    }
}
