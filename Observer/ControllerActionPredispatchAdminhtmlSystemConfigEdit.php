<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Observer;

use Magento\Config\Controller\Adminhtml\System\Config\Edit;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use SoapFault;
use TNW\Salesforce\Client\Salesforce;
use TNW\Salesforce\Model\Config;

/**
 * Predispatch Observer
 */
class ControllerActionPredispatchAdminhtmlSystemConfigEdit implements ObserverInterface
{
    /** @var UrlInterface */
    private $urlBuilder;

    /** @var ManagerInterface */
    private $messageManager;

    /** @var Salesforce */
    private $salesforceClient;

    /** @var array */
    protected $allowSection = [
        'tnwsforce_customer',
        'tnwsforce_product',
        'tnwsforce_order',
        'tnwsforce_invoice',
        'tnwsforce_shipment',
        'tnwsforce_picklists',
    ];

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var Config */
    private $salesforceConfig;

    /**
     * @param UrlInterface          $urlBuilder
     * @param ManagerInterface      $messageManager
     * @param Salesforce            $salesforceClient
     * @param Config                $salesforceConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        UrlInterface $urlBuilder,
        ManagerInterface $messageManager,
        Salesforce $salesforceClient,
        Config $salesforceConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->messageManager = $messageManager;
        $this->salesforceClient = $salesforceClient;
        $this->storeManager = $storeManager;
        $this->salesforceConfig = $salesforceConfig;
    }

    /**
     * @param Observer $observer
     *
     * @throws FileSystemException|LocalizedException
     */
    public function execute(Observer $observer)
    {
        /** @var Http $request */
        $request = $observer->getEvent()->getData('request');
        if (!in_array($request->getParam('section'), $this->allowSection)) {
            return;
        }

        /** @var Edit $controllerAction */
        $controllerAction = $observer->getEvent()->getData('controller_action');

        if (!$this->salesforceClient->getClientStatus()) {
            $this->messageManager->addWarningMessage('Saleseforce Integration is disable');
            $this->redirect($controllerAction);
            return;
        }

        $websiteId = $this->storeManager->getWebsite()->getId();
        $wsdl = $this->salesforceConfig->getSalesforceWsdl($websiteId);
        $userName = $this->salesforceConfig->getSalesforceUsername($websiteId);
        $password = $this->salesforceConfig->getSalesforcePassword($websiteId);
        $token = $this->salesforceConfig->getSalesforceToken($websiteId);

        try {
            $this->salesforceClient->checkConnection($wsdl, $userName, $password, $token);
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
//            $this->messageManager->addErrorMessage(__('Duplication message'));
//            $this->messageManager->addErrorMessage(__('Duplication message'));
//            $this->messageManager->addErrorMessage(__('Duplication message1'));

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
        $action->getActionFlag()->set('', Edit::FLAG_NO_DISPATCH, true);

        $response = $action->getResponse();
        $url = $this->urlBuilder->getUrl('adminhtml/system_config/edit', ['section'=>'tnwsforce_general']);
        $response->setRedirect($url);
    }
}
