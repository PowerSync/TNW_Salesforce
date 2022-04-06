<?php
namespace TNW\Salesforce\Observer;

use Exception;
use Magento\Config\Controller\Adminhtml\System\Config\Edit;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use SoapFault;
use TNW\Salesforce\Client\Salesforce;

/**
 * Predispatch Observer
 */
class ControllerActionPredispatchAdminhtmlSystemConfigEdit implements ObserverInterface
{
    private const TNW_SECTION_PREFIX = 'tnwsforce_';
    private const TNW_GENERAL_SECTION = 'tnwsforce_general';

    /** @var UrlInterface */
    private $urlBuilder;

    /** @var ManagerInterface */
    private $messageManager;

    /** @var Salesforce */
    private $salesforceClient;

    /**
     * @param UrlInterface     $urlBuilder
     * @param ManagerInterface $messageManager
     * @param Salesforce       $salesforceClient
     */
    public function __construct(
        UrlInterface $urlBuilder,
        ManagerInterface $messageManager,
        Salesforce $salesforceClient
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->messageManager = $messageManager;
        $this->salesforceClient = $salesforceClient;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var Http $request */
        $request = $observer->getEvent()->getData('request');
        if (!$this->isAllowedSection((string)$request->getParam('section'))) {
            return;
        }

        /** @var Edit $controllerAction */
        $controllerAction = $observer->getEvent()->getData('controller_action');

        if (!$this->salesforceClient->getClientStatus()) {
            $this->messageManager->addWarningMessage('Saleseforce Integration is disable');
            $this->redirect($controllerAction);

            return;
        }

        try {
            $client = $this->salesforceClient->getClient();
            $client->getUserInfo();
        } catch (SoapFault $e) {
            switch (true) {
                case strcasecmp($e->faultcode, 'sf:INVALID_OPERATION_WITH_EXPIRED_PASSWORD') === 0:
                    $this->messageManager->addErrorMessage(__('You Salesforce password is expired. Login to Salesforce, update password. Put new Password and token in our module configuration.'));
                    break;

                case strcasecmp($e->faultcode, 'sf:INVALID_LOGIN') === 0:
                    $this->messageManager->addErrorMessage(__('Defined Salesforce login, password or token is incorrect. Please defined valid information.'));
                    break;

                case strcasecmp($e->faultcode, 'WSDL') === 0:
                    $this->messageManager->addErrorMessage(__('The WSDL file is no available or corrupted. Upload new wsdl file.'));
                    break;

                default:
                    $this->messageManager->addExceptionMessage($e);
                    break;
            }

            $this->redirect($controllerAction);

            return;
        } catch (Exception $e) {
            $this->messageManager->addExceptionMessage($e);
            $this->redirect($controllerAction);
        }
    }

    /**
     * @param Action $action
     */
    protected function redirect(Action $action)
    {
        $action->getActionFlag()->set('', Edit::FLAG_NO_DISPATCH, true);

        $response = $action->getResponse();
        $url = $this->urlBuilder->getUrl('adminhtml/system_config/edit', ['section'=>'tnwsforce_general']);
        $response->setRedirect($url);
    }

    /**
     * @param string $sectionName
     *
     * @return bool
     */
    private function isAllowedSection(string $sectionName): bool
    {
        return (strpos($sectionName, self::TNW_SECTION_PREFIX) !== false)
            && $sectionName !== self::TNW_GENERAL_SECTION;
    }
}
