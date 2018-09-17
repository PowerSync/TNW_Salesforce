<?php
namespace TNW\Salesforce\Test\Unit\Controller\Adminhtml\System\Config;

/**
 * Class TestconnectionTest
 * @package TNW\Salesforce\Test\Unit\Controller\Adminhtml\System\Config
 */
class TestconnectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \TNW\Salesforce\Controller\Adminhtml\System\Config\Testconnection
     */
    protected $controller;

    /**
     * @var \Magento\Framework\App\RequestInterface mock
     */
    protected $request;

    /**
     * @var \Magento\Framework\App\ResponseInterface mock
     */
    protected $response;

    /**
     * @var \Magento\Backend\App\Action\Context mock
     */
    protected $contextMock;

    /**
     * @var \Magento\Framework\ObjectManagerInterface mock
     */
    protected $objectManagerMock;


    /**
     * Test Set Up
     */
    protected function setUp()
    {
        $this->contextMock = $this->getMock(
            'Magento\Backend\App\Action\Context',
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
        $this->request = $this->getMock('Magento\Framework\App\RequestInterface');
        $this->response = $this->getMockForAbstractClass(
            'Magento\Framework\App\ResponseInterface',
            [],
            '',
            false,
            true,
            true,
            ['setBody']
        );
        $this->contextMock->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->contextMock->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));
        $this->contextMock->expects(
            $this->once()
        )->method(
            'getObjectManager'
        )->will(
            $this->returnValue($this->objectManagerMock)
        );

        $this->controller = new \TNW\Salesforce\Controller\Adminhtml\System\Config\Testconnection($this->contextMock);
    }

    /**
     * Test for \TNW\Salesforce\Controller\Adminhtml\System\Store\Testconnection::execute
     * @param $testData
     *
     * @dataProvider executeDataProvider
     */
    public function testExecute($testData)
    {
        $this->request->expects(
            $this->once()
        )->method(
            'getParam'
        )->with(
            'website'
        )->will(
            $this->returnValue($testData['website'])
        );

        $salesforceClientMock = $this->getMock(
            'TNW\Salesforce\Client\Salesforce',
            [],
            [],
            '',
            false
        );
        $salesforceConfigMock = $this->getMock(
            'TNW\Salesforce\Model\Config',
            [],
            [],
            '',
            false
        );
        $salesforceConfigMock->expects(
            $this->once()
        )->method(
            'getSalesforceWsdl'
        )->with(
            $testData['website']
        )->will(
            $this->returnValue($testData['wsdl'])
        );
        $salesforceConfigMock->expects(
            $this->once()
        )->method(
            'getSalesforceUsername'
        )->with(
            $testData['website']
        )->will(
            $this->returnValue($testData['username'])
        );
        $salesforceConfigMock->expects(
            $this->once()
        )->method(
            'getSalesforcePassword'
        )->with(
            $testData['website']
        )->will(
            $this->returnValue($testData['password'])
        );
        $salesforceConfigMock->expects(
            $this->once()
        )->method(
            'getSalesforceToken'
        )->with(
            $testData['website']
        )->will(
            $this->returnValue($testData['token'])
        );

        $this->objectManagerMock->expects(
            $this->any()
        )->method(
            'get'
        )->will(
            $this->returnValueMap([
                ['\TNW\Salesforce\Client\Salesforce', $salesforceClientMock],
                ['\TNW\Salesforce\Model\Config', $salesforceConfigMock]
            ])
        );

        $salesforceClientMock->expects(
            $this->once()
        )->method(
            'checkConnection'
        )->with(
            $testData['wsdl'],
            $testData['username'],
            $testData['password'],
            $testData['token']
        )->will(
            array_key_exists('exception', $testData)
                ? $this->throwException(new \Exception($testData['exception']))
                : $this->returnValue($testData['checkConnectionResult'])
        );
        $this->response->expects(
            $this->once()
        )->method(
            'setBody'
        )->with(
            array_key_exists('exception', $testData)
                ? $testData['exception']
                : $testData['checkConnectionResult']
        );

        $this->controller->execute();
    }

    /**
     * DataProvider for testExecute
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            'No exception' => [[
                'website' => 1,
                'wsdl' => 'wsdl',
                'username' => 'username',
                'password' => 'password',
                'token' => 'token',
                'checkConnectionResult' => true
            ]],
            'Exception on checkConnection' => [[
                'exception' => 'Some data not valid',
                'website' => 1,
                'wsdl' => 'wsdl',
                'username' => 'username',
                'password' => 'password',
                'token' => 'token',
                'checkConnectionResult' => false
            ]]
        ];
    }
}
