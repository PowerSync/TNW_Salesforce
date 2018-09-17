<?php
namespace TNW\Salesforce\Test\Unit\Controller\Adminhtml\System\Store;

/**
 * Class SyncAllTest
 * @package TNW\Salesforce\Test\Unit\Controller\Adminhtml\System\Store
 */
class SyncAllTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \TNW\Salesforce\Controller\Adminhtml\System\Store\SyncAll
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
        /** @var $coreRegistryMock \Magento\Framework\Registry */
        $coreRegistryMock = $this->getMock(
            'Magento\Framework\Registry',
            [],
            [],
            '',
            false
        );
        /** @var $filterManagerMock \Magento\Framework\Filter\FilterManager */
        $filterManagerMock = $this->getMock(
            'Magento\Framework\Filter\FilterManager',
            [],
            [],
            '',
            false
        );
        /** @var $forwardFactoryMock \Magento\Backend\Model\View\Result\ForwardFactory */
        $forwardFactoryMock = $this->getMock(
            'Magento\Backend\Model\View\Result\ForwardFactory',
            [],
            [],
            '',
            false
        );
        /** @var $pageFactoryMock \Magento\Framework\View\Result\PageFactory */
        $pageFactoryMock = $this->getMock(
            'Magento\Framework\View\Result\PageFactory',
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

        $this->controller = new \TNW\Salesforce\Controller\Adminhtml\System\Store\SyncAll(
            $this->contextMock,
            $coreRegistryMock,
            $filterManagerMock,
            $forwardFactoryMock,
            $pageFactoryMock
        );
    }

    /**
     * Test for \TNW\Salesforce\Controller\Adminhtml\System\Store\SyncAll::execute
     * @param $testData
     * @param $expectedRedirectRoute
     *
     * @dataProvider executeDataProvider
     */
    public function testExecute($testData, $expectedRedirectRoute)
    {
        $this->resultRedirectMock->expects($this->once())
            ->method('setPath')
            ->with($expectedRedirectRoute)
            ->willReturnSelf();

        $websiteRepositoryMock = $this->getMock(
            'Magento\Store\Api\WebsiteRepositoryInterface',
            [],
            [],
            '',
            false
        );
        $apiWebsiteMock = $this->getMock(
            'TNW\Salesforce\Api\WebsiteInterface',
            [],
            [],
            '',
            false
        );
        $this->objectManagerMock->expects(
            $this->any()
        )->method(
            'create'
        )->will(
            $this->returnValueMap([
                ['Magento\Store\Api\WebsiteRepositoryInterface', [], $websiteRepositoryMock],
                ['TNW\Salesforce\Api\WebsiteInterface', [], $apiWebsiteMock]
            ])
        );
        $websiteListMock = [
            $this->getMock(
                'Magento\Store\Api\Data\WebsiteInterface',
                [],
                [],
                '',
                false
            ),
            $this->getMock(
                'Magento\Store\Api\Data\WebsiteInterface',
                [],
                [],
                '',
                false
            )
        ];
        $websiteRepositoryMock->expects(
            $this->once()
        )->method(
            'getList'
        )->will(
            $this->returnValue($websiteListMock)
        );
        $apiWebsiteMock->expects(
            $this->once()
        )->method(
            'syncWebsites'
        )->with(
            $websiteListMock
        )->will(
            array_key_exists('exception', $testData)
                ? $this->throwException(new \Exception($testData['exception']))
                : $this->returnValue('SomeValue')
        );
        if (array_key_exists('exception', $testData)) {
            $this->messageManagerMock
                ->expects($this->once())
                ->method('addError')
                ->with($this->equalTo($testData['exception']))
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
            'No exception' => [[], 'adminhtml/*/'],
            'Exception on website sync' => [['exception' => 'websites not synced'], 'adminhtml/*/']
        ];
    }
}
