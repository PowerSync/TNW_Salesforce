<?php
namespace TNW\Salesforce\Controller\Adminhtml\Wizard;

/**
 * Class Testconnection
 * @package TNW\Salesforce\Controller\Adminhtml\Wizard
 */
class Testconnection extends \Magento\Backend\App\Action
{
    /**
     * Perform Salesforce connection test
     *
     * @return \Magento\Framework\Object
     */
    public function execute()
    {
        $result = array();
        $wsdl = '';
        $username = '';
        $password = '';
        $token = '';
        $groups = $this->getRequest()->getParam('groups');
        foreach ($groups as $groupName => $group) {
            if (isset($group['fields']) && is_array($group['fields'])) {
                foreach ($group['fields'] as $fieldName => $field) {
                    switch($fieldName) {
                        case 'username':
                            $username = $field;
                            break;
                        case 'password':
                            $password = $field;
                            break;
                        case 'token':
                            $token = $field;
                            break;
                        case 'wsdl':
                            $wsdl = $field;
                            break;
                        default:break;
                    }
                }
            }
        }
        /**
         * @var \TNW\Salesforce\Client\Salesforce $client
         */
        $client = $this->_objectManager->get('\TNW\Salesforce\Client\Salesforce');
        try {
            $result['success'] = $client->checkConnection($wsdl, $username, $password, $token);
        } catch (\Exception $e) {
            $result['msg'] = $e->getMessage();
        }
        $this->getResponse()->setBody(json_encode($result));
    }
}
