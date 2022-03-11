<?php
namespace TNW\Salesforce\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\App\Action\Action;

/**
 * Predispatch Observer
 */
class ControllerActionPredispatchAdminhtmlSystemConfigEdit implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $urlBuilder;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * @var \TNW\Salesforce\Client\Salesforce
     */
    private $salesforceClient;

    /**
     * @var string[]
     */
    private $allowSection;

    /**
     * ControllerActionPredispatchAdminhtmlSystemConfigEdit constructor.
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \TNW\Salesforce\Client\Salesforce $salesforceClient
     * @param string[] $allowSection
     */
    public function __construct(
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \TNW\Salesforce\Client\Salesforce $salesforceClient,
        array $allowSection = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->messageManager = $messageManager;
        $this->salesforceClient = $salesforceClient;
        $this->allowSection = $allowSection;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $observer->getEvent()->getData('request');
        if (!in_array($request->getParam('section'), $this->allowSection)) {
            return;
        }

        /** @var \Magento\Config\Controller\Adminhtml\System\Config\Edit $controllerAction */
        $controllerAction = $observer->getEvent()->getData('controller_action');

        if (!$this->salesforceClient->getClientStatus()) {
            $this->messageManager->addWarningMessage('Salesforce Integration is disabled.');
            $this->redirect($controllerAction);
            return;
        }

        try {
            $client = $this->salesforceClient->getClient();
            $client->getUserInfo();
        } catch (\SoapFault $e) {
            switch (true) {
                case strcasecmp($e->faultcode, 'sf:INVALID_OPERATION_WITH_EXPIRED_PASSWORD') === 0:
                    $this->messageManager->addErrorMessage(__('Your Salesforce password has expired. Please login to Salesforce and update the password. Put a new password and token in our module configuration.'));
                    break;

                case strcasecmp($e->faultcode, 'sf:INVALID_LOGIN') === 0:
                    $this->messageManager->addErrorMessage(__('Provided Salesforce login, password, or token is incorrect. Please provide valid information.'));
                    break;

                case strcasecmp($e->faultcode, 'WSDL') === 0:
                    $this->messageManager->addErrorMessage(__('The WSDL file is not available or corrupted. Please, upload a new WSDL file.'));
                    break;

                default:
                    $this->messageManager->addExceptionMessage($e);
                    break;
            }

            $this->redirect($controllerAction);
            return;
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e);
            $this->redirect($controllerAction);
        }
    }

    /**
     * @param Action $action
     */
    protected function redirect(Action $action)
    {
        $action->getActionFlag()->set('', \Magento\Config\Controller\Adminhtml\System\Config\Edit::FLAG_NO_DISPATCH, true);

        /** @var \Magento\Framework\App\Response\Http $response */
        $response = $action->getResponse();
        $response->setRedirect($this->urlBuilder->getUrl('adminhtml/system_config/edit', ['section'=>'tnwsforce_general']));
    }
}
