<?php
namespace TNW\Salesforce\Controller\Adminhtml\System\Config;

/**
 * Class Testconnection
 * @package TNW\Salesforce\Controller\Adminhtml\System\Config
 */
class Testconnection extends \Magento\Backend\App\Action
{
    /**
     * Perform Salesforce connection test
     */
    public function execute()
    {
        /**
         * @var \TNW\Salesforce\Client\Salesforce $client
         */
        $client = $this->_objectManager->get('\TNW\Salesforce\Client\Salesforce');

        /**
         * @var \TNW\Salesforce\Model\Config $config
         */
        $config = $this->_objectManager->get('\TNW\Salesforce\Model\Config');

        // get website id from url
        $websiteId = $this->getRequest()->getParam('website');

        // read website specific configuration
        $wsdl = $config->getSalesforceWsdl($websiteId);
        $username = $config->getSalesforceUsername($websiteId);
        $password = $config->getSalesforcePassword($websiteId);
        $token = $config->getSalesforceToken($websiteId);

        try {
            $result = $client->checkConnection($wsdl, $username, $password, $token);
        } catch (\Exception $e) {
            $result = $e->getMessage();
        }
        $this->getResponse()->setBody($result);
    }
}
