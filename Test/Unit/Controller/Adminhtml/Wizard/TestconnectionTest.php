<?php
namespace TNW\Salesforce\Test\Unit\Controller\Adminhtml\Wizard;

/**
 * Class TestconnectionTest
 * @package TNW\Salesforce\Test\Unit\Controller\Adminhtml\Wizard
 */
class TestconnectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \TNW\Salesforce\Controller\Adminhtml\Wizard\Testconnection
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

        $this->controller = new \TNW\Salesforce\Controller\Adminhtml\Wizard\Testconnection($this->contextMock);
    }

    /**
     * Test Tear Down
     */
    public function tearDown()
    {
        $this->objectManagerMock = null;
        $this->controller        = null;
        $this->contextMock       = null;
        $this->request           = null;
        $this->response          = null;
    }

    /**
     * Test for \TNW\Salesforce\Controller\Adminhtml\Wizard\Testconnection::execute
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
            'groups'
        )->will(
            $this->returnValue($testData['groups'])
        );

        $salesforceClientMock = $this->getMock(
            'TNW\Salesforce\Client\Salesforce',
            [],
            [],
            '',
            false
        );

        $this->objectManagerMock->expects(
            $this->once()
        )->method(
            'get'
        )->with(
            '\TNW\Salesforce\Client\Salesforce'
        )->will(
            $this->returnValue($salesforceClientMock)
        );

        $salesforceClientMock->expects(
            $this->once()
        )->method(
            'checkConnection'
        )->with(
            $testData['groups']['salesforce']['fields']['wsdl'],
            $testData['groups']['salesforce']['fields']['username'],
            $testData['groups']['salesforce']['fields']['password'],
            $testData['groups']['salesforce']['fields']['token']
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
                ? json_encode(['msg' => $testData['exception']])
                : json_encode(['success' => $testData['checkConnectionResult']])
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
                'groups' => [
                    'salesforce' => [
                        'fields' => [
                            'wsdl' => 'wsdl',
                            'username' => 'username',
                            'password' => 'password',
                            'token' => 'token'
                        ]
                    ]
                ],
                'checkConnectionResult' => true
            ]],
            'Exception on checkConnection' => [[
                'exception' => 'Some data not valid',
                'groups' => [
                    'salesforce' => [
                        'fields' => [
                            'wsdl' => 'wsdl',
                            'username' => 'username',
                            'password' => 'password',
                            'token' => 'token'
                        ]
                    ]
                ],
                'checkConnectionResult' => false
            ]]
        ];
    }
}
