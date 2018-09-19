<?php
namespace TNW\Salesforce\Controller\Adminhtml\Wizard;

/**
 * Class Websites
 * @package TNW\Salesforce\Controller\Adminhtml\Wizard
 */
class Websites extends \Magento\Backend\App\Action
{
    /**
     * Action
     */
    public function execute()
    {
        $unsyncedWebsites = $this->getRequest()->getParam('websites');
        $websitesModel = $this->_objectManager->get('\TNW\Salesforce\Model\Website');
        $result['websites'] = $websitesModel->syncWebsites($unsyncedWebsites);
        $this->getResponse()->setBody(json_encode($result));
    }
}
