<?php
namespace TNW\Salesforce\Controller\Adminhtml\Wizard;

/**
 * Class Tests
 * @package TNW\Salesforce\Controller\Adminhtml\Wizard
 */
class Tests extends \Magento\Backend\App\Action
{
    /**
     * Action
     */
    public function execute()
    {
        $failedTests = $this->getRequest()->getParam('tests');
        $testCollection = $this->_objectManager->get('\TNW\Salesforce\Model\TestCollection');
        $result['tests'] = $testCollection->getSalesforceDependencies($failedTests);
        $this->getResponse()->setBody(json_encode($result));
    }
}
