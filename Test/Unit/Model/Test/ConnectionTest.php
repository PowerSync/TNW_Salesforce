<?php
namespace TNW\Salesforce\Test\Unit\Model\Test;

/**
 * Class ConnectionTest
 * @package TNW\Salesforce\Test\Unit\Model\Test
 */
class ConnectionTest extends \PHPUnit_Framework_TestCase
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
     * @var \TNW\Salesforce\Model\Test\Connection
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

        $this->model = new \TNW\Salesforce\Model\Test\Connection(
            $this->objectManagerMock,
            $this->configMock
        );
    }

    /**
     * Test for \TNW\Salesforce\Model\Test\Connection::execute
     *
     * @param $checkConnectionResult
     * @param $expectedResult
     * @param $configData
     *
     * @dataProvider testExecuteDataProvider
     */
    public function testExecute($checkConnectionResult, $configData, $expectedResult)
    {
        $this->configMock->expects(
            $this->any()
        )->method(
            'getSalesforceWsdl'
        )->will(
            $this->returnValue($configData['Wsdl'])
        );

        $this->configMock->expects(
            $this->any()
        )->method(
            'getSalesforceUsername'
        )->will(
            $this->returnValue($configData['Username'])
        );

        $this->configMock->expects(
            $this->any()
        )->method(
            'getSalesforcePassword'
        )->will(
            $this->returnValue($configData['Password'])
        );

        $this->configMock->expects(
            $this->any()
        )->method(
            'getSalesforceToken'
        )->will(
            $this->returnValue($configData['Token'])
        );

        $this->clientSalesforceMock->expects(
            $this->any()
        )->method(
            'checkConnection'
        )->will(
            $checkConnectionResult['type'] == 'bool'
                ? $this->returnValue($checkConnectionResult['result'])
                : $checkConnectionResult['result']
        );

        $this->objectManagerMock->expects(
            $this->any()
        )->method(
            'get'
        )->with(
            '\TNW\Salesforce\Client\Salesforce'
        )->will(
            $this->returnValue($this->clientSalesforceMock)
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
        $exception = $this->throwException(new \Exception('Error'));
        $configData = [
            'Wsdl' => 'var/wsdl.xml',
            'Username' => 'Test User 1',
            'Password' => '123456789',
            'Token' => 'qwertyuio'
        ];

        return [
            [['result' => true, 'type' => 'bool'], $configData, 'passed'],
            [['result' => $exception, 'type' => 'exception'], $configData, 'failed'],
        ];
    }

    /**
     * Test tear down
     */
    public function tearDown()
    {
        $this->objectManagerMock    = null;
        $this->configMock           = null;
        $this->clientSalesforceMock = null;
        $this->model                = null;
    }
}
