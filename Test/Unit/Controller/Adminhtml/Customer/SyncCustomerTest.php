<?php
namespace TNW\Salesforce\Test\Unit\Controller\Adminhtml\Customer;

/**
 * Class SyncCustomerTest
 * @package TNW\Salesforce\Test\Unit\Controller\Adminhtml\Customer
 */
class SyncCustomerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \TNW\Salesforce\Controller\Adminhtml\Customer\SyncCustomer
     */
    protected $controller;

    /**
     * @var \Magento\Backend\App\Action\Context mock
     */
    protected $contextMock;

    /**
     * @var \Magento\Framework\Controller\Result\Redirect mock
     */
    protected $resultRedirectMock;

    /**
     * @var \Magento\Framework\ObjectManagerInterface mock
     */
    protected $objectManagerMock;

    /**
     * @var \Magento\Framework\Message\ManagerInterface mock
     */
    protected $messageManagerMock;

    /**
     * @var \Magento\Framework\App\RequestInterface mock
     */
    protected $request;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface mock
     */
    protected $customerRepositoryMock;

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
        /** @var $resultRedirectFactoryMock \Magento\Framework\Controller\Result\RedirectFactory */
        $resultRedirectFactoryMock = $this->getMock(
            'Magento\Framework\Controller\Result\RedirectFactory',
            [],
            [],
            '',
            false
        );
        $this->resultRedirectMock = $this->getMock(
            'Magento\Framework\Controller\Result\Redirect',
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
        $this->messageManagerMock = $this->getMock(
            'Magento\Framework\Message\ManagerInterface',
            [],
            [],
            '',
            false
        );
        $this->customerRepositoryMock = $this->getMock(
            'Magento\Customer\Api\CustomerRepositoryInterface',
            [],
            [],
            '',
            false
        );
        $this->request = $this->getMock('Magento\Framework\App\RequestInterface');

        $resultRedirectFactoryMock->expects(
            $this->once()
        )->method(
            'create'
        )->will(
            $this->returnValue($this->resultRedirectMock)
        );
        $this->contextMock->expects(
            $this->once()
        )->method(
            'getMessageManager'
        )->will(
            $this->returnValue($this->messageManagerMock)
        );
        $this->contextMock->expects(
            $this->once()
        )->method(
            'getObjectManager'
        )->will(
            $this->returnValue($this->objectManagerMock)
        );
        $this->contextMock->expects(
            $this->once()
        )->method(
            'getResultRedirectFactory'
        )->will(
            $this->returnValue($resultRedirectFactoryMock)
        );
        $this->contextMock->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->controller = $objectManagerHelper->getObject(
            'TNW\Salesforce\Controller\Adminhtml\Customer\SyncCustomer',
            [
                'context' => $this->contextMock,
                'customerRepository' => $this->customerRepositoryMock,
            ]
        );
    }

    /**
     * Test tear down
     */
    public function tearDown()
    {
        $this->controller             = null;
        $this->resultRedirectMock     = null;
        $this->contextMock            = null;
        $this->objectManagerMock      = null;
        $this->messageManagerMock     = null;
        $this->customerRepositoryMock = null;
        $this->request                = null;
    }

    /**
     * Test for \TNW\Salesforce\Controller\Adminhtml\Customer\SyncCustomer::execute
     * @param $testData
     * @param $expectedRedirectRoute
     * @param $expectedRedirectRouteParams
     *
     * @dataProvider executeDataProvider
     */
    public function testExecute($testData, $expectedRedirectRoute, $expectedRedirectRouteParams)
    {
        $this->resultRedirectMock->expects($this->once())
            ->method('setPath')
            ->with($expectedRedirectRoute, $expectedRedirectRouteParams)
            ->willReturnSelf();
        $this->request->expects(
            $this->once()
        )->method(
            'getParam'
        )->with(
            'customer_id'
        )->will(
            $this->returnValue($testData['customer_id'])
        );

        $customerClientMock = $this->getMock(
            'TNW\Salesforce\Client\Customer',
            [],
            [],
            '',
            false
        );
        $customerMock = $this->getMock(
            'Magento\Customer\Api\Data\CustomerInterface',
            [],
            [],
            '',
            false
        );

        $this->customerRepositoryMock->expects(
            $this->once()
        )->method(
            'getById'
        )->with(
            $testData['customer_id']
        )->will(
            $this->returnValue($customerMock)
        );
        $this->objectManagerMock->expects(
            $this->once()
        )->method(
            'create'
        )->with(
            'TNW\Salesforce\Client\Customer'
        )->will(
            $this->returnValue($customerClientMock)
        );

        $customerClientMock->expects(
            $this->once()
        )->method(
            'syncCustomers'
        )->with(
            [$customerMock],
            true
        )->will(
            array_key_exists('exception', $testData)
                ? $this->throwException(new \Exception($testData['exception']))
                : $this->returnValue($testData['result_sync_customers'])
        );
        if (array_key_exists('exception', $testData) || array_key_exists('success', $testData)) {
            $this->messageManagerMock
                ->expects(
                    $this->once()
                )
                ->method(
                    array_key_exists('exception', $testData)
                        ? 'addError'
                        : 'addSuccessMessage'
                )
                ->with(
                    array_key_exists('exception', $testData)
                        ? $this->equalTo($testData['exception'])
                        : $this->equalTo($testData['success'])
                )
                ->willReturnSelf();
        }

        $this->assertSame($this->resultRedirectMock, $this->controller->execute());
    }

    /**
     * DataProvider for testExecute
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            'No exception, result data' => [
                [
                    'customer_id' => 'customer_id',
                    'success' => 'Magento customers were successfully synchronized',
                    'result_sync_customers' => true
                ],
                'customer/index/edit',
                ['id' => 'customer_id']
            ],
            'No exception, no result data' => [
                [
                    'customer_id' => 'customer_id',
                    'result_sync_customers' => false
                ],
                'customer/index/edit',
                ['id' => 'customer_id']
            ],
            'Exception on customer sync' => [
                [
                    'customer_id' => 'customer_id',
                    'exception' => 'websites not synced'
                ],
                'customer/index/edit',
                ['id' => 'customer_id']
            ]
        ];
    }
}
