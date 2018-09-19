<?php
namespace TNW\Salesforce\Test\Unit\Controller\Adminhtml\Wizard;

/**
 * Class TestsTest
 * @package TNW\Salesforce\Test\Unit\Controller\Adminhtml\Wizard
 */
class TestsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \TNW\Salesforce\Controller\Adminhtml\Wizard\Tests
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

        $this->controller = new \TNW\Salesforce\Controller\Adminhtml\Wizard\Tests($this->contextMock);
    }

    /**
     * Test Tear Down
     */
    public function tearDown()
    {
        $this->controller        = null;
        $this->contextMock       = null;
        $this->request           = null;
        $this->response          = null;
        $this->objectManagerMock = null;
    }

    /**
     * Test for \TNW\Salesforce\Controller\Adminhtml\Wizard\Tests::execute
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
            'tests'
        )->will(
            $this->returnValue($testData['tests'])
        );

        $salesforceTestCollectionMock = $this->getMock(
            'TNW\Salesforce\Model\TestCollection',
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
            '\TNW\Salesforce\Model\TestCollection'
        )->will(
            $this->returnValue($salesforceTestCollectionMock)
        );

        $salesforceTestCollectionMock->expects(
            $this->once()
        )->method(
            'getSalesforceDependencies'
        )->with(
            $testData['tests']
        )->will(
            $this->returnValue($testData['salesforceDependencies'])
        );
        $this->response->expects(
            $this->once()
        )->method(
            'setBody'
        )->with(
            json_encode(['tests' => $testData['salesforceDependencies']])
        );

        $this->controller->execute();
    }

    /**
     * DataProvider for testExecute
     * @return array
     */
    public function executeDataProvider()
    {
        return [[['tests' => ['test1', 'test2'], 'salesforceDependencies' => 'salesforceDependencies']]];
    }
}
