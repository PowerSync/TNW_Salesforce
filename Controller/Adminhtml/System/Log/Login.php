<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Controller\Adminhtml\System\Log;

class Login extends \Magento\Backend\App\Action
{

    /**
     * @var \TNW\Salesforce\Client\Salesforce
     */
    private $salesforce;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \TNW\Salesforce\Client\Salesforce $salesforce
    ) {
        parent::__construct($context);
        $this->salesforce = $salesforce;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('TNW_Salesforce::tools_login');
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|\Magento\Framework\App\ResponseInterface
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function execute()
    {
        $websiteId = $this->_request->getParam('website_id');

        try {
            $client = $this->salesforce->getClient($websiteId);
            if ($client) {
                $loginResult = $client->getLoginResult();

                return $this->resultRedirectFactory->create()
                    ->setUrl(sprintf(
                        'https://%s.salesforce.com/secur/frontdoor.jsp?sid=%s',
                        $loginResult->getServerInstance(),
                        $loginResult->getSessionId()
                    ));
            }
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e);
        }

        return $this->resultRedirectFactory->create()
            ->setPath('tnw_salesforce/system_log/view');
    }
}
