<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Observer;

use Exception;
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
    private const TNW_SECTION_PREFIX = 'tnwsforce_';
    private const TNW_GENERAL_SECTION = 'tnwsforce_general';

    /** @var UrlInterface */
    private $urlBuilder;

    /** @var ManagerInterface */
    private $messageManager;

    /** @var Salesforce */
    private $salesforceClient;

    /** @var string[] */
    private $allowSection;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var Config */
    private $salesforceConfig;

    /**
     * @param UrlInterface $urlBuilder
     * @param ManagerInterface $messageManager
     * @param Salesforce $salesforceClient
     * @param Config $salesforceConfig
     * @param StoreManagerInterface $storeManager
     * @param array $allowSection
     */
    public function __construct(
        UrlInterface $urlBuilder,
        ManagerInterface $messageManager,
        Salesforce $salesforceClient,
        Config $salesforceConfig,
        StoreManagerInterface $storeManager,
        array $allowSection = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->messageManager = $messageManager;
        $this->salesforceClient = $salesforceClient;
        $this->storeManager = $storeManager;
        $this->salesforceConfig = $salesforceConfig;
        $this->allowSection = $allowSection;
    }

    /**
     * @param Observer $observer
     * @throws FileSystemException|LocalizedException
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
            $this->messageManager->addWarningMessage('Saleseforce Integration is disabled');
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
