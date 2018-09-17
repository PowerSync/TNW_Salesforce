<?php
namespace TNW\Salesforce\Test\Unit\Model\Test;

/**
 * Class LicenseTest
 * @package TNW\Salesforce\Test\Unit\Model\Test
 */
class LicenseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface mock
     */
    protected $objectManagerMock;

    /**
     * @var \TNW\Salesforce\Model\Config mock
     */
    protected $configMock;

    /**
     * @var \TNW\Salesforce\Client\Salesforce mock
     */
    protected $clientSalesforceMock;

    /**
     * @var \Tnw\SoapClient\Client mock
     */
    protected $soapClientMock;

    /**
     * @var \TNW\Salesforce\Model\Test\License
     */
    protected $model;

    /**
     * Test setup
     */
    public function setUp()
    {
        $this->objectManagerMock = $this->getMock(
            'Magento\Framework\ObjectManagerInterface',
            [],
            [],
            '',
            false
        );

        $this->configMock = $this->getMock(
            'TNW\Salesforce\Model\Config',
            [],
            [],
            '',
            false
        );

        $this->clientSalesforceMock = $this->getMock(
            'TNW\Salesforce\Client\Salesforce',
            [],
            [],
            '',
            false
        );

        $this->soapClientMock = $this->getMock(
            'Tnw\SoapClient\Client',
            [],
            [],
            '',
            false
        );

        $this->model = new \TNW\Salesforce\Model\Test\License(
            $this->objectManagerMock,
            $this->configMock,
            $this->clientSalesforceMock
        );
    }

    /**
     * Test for \TNW\Salesforce\Model\Test\License::execute
     *
     * @dataProvider testExecuteDataProvider
     */
    public function testExecute($describeSObjectsResult, $expectedResult)
    {
        $this->soapClientMock->expects(
            $this->any()
        )->method(
            'describeSObjects'
        )->will(
            $expectedResult == 'passed'
                ? $this->returnValue($describeSObjectsResult)
                : $describeSObjectsResult
        );

        $this->clientSalesforceMock->expects(
            $this->any()
        )->method(
            'getClient'
        )->will(
            $this->returnValue($this->soapClientMock)
        );

        $this->model->execute();
        $this->assertEquals($expectedResult, $this->model->getStatus());
    }

    /**
     * Data provider for testExecute
     *
     * @return array
     */
    public function testExecuteDataProvider()
    {
        $resultObject = [
            [$this->getMock(
                'Tnw\SoapClient\Result\DescribeSObjectResult',
                [],
                [],
                '',
                false
            )]
        ];

        $exception = $this->throwException(new \Exception('Error'));

        return [
            [$resultObject, 'passed'],
            [$exception, 'failed'],
        ];
    }

    /**
     * Test tear down
     */
    public function tearDown()
    {
        $this->model = null;
    }
}
