<?php

namespace TNW\Salesforce\Block\Adminhtml\Customer\Edit\Renderer;

use Magento\Customer\Controller\RegistryConstants;
use TNW\SForceEnterprise\Model\Cron\Source\MagentoObjectType;

/**
 * Class SForceId
 * @package TNW\Salesforce\Block\Adminhtml\Customer\Edit\Renderer
 */
class SForceId extends \TNW\Salesforce\Block\Adminhtml\Base\Edit\Renderer\SForceId
{

    /**
     * @return integer
     */
    public function getEntityId()
    {
        return $this->registry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
    }

    /**
     * @return string
     */
    public function getMagentoObjectType()
    {
        return MagentoObjectType::OBJECT_TYPE_CUSTOMER;
    }

    /**
     * @return null|string
     * @throws \Exception
     */
    public function getSalesforceObjectByAttribute()
    {
        $salesforceObject = null;
        switch ($this->getId()) {
            case 'sforce_id':
                $salesforceObject = 'Contact';
                break;

            case 'sforce_account_id':
                $salesforceObject = 'Account';
                break;

            case 'sforce_lead_id':
                $salesforceObject = 'Lead';
                break;
            default:
                throw new \Exception(__('Unknown attribute: %1', $this->getId()));
                break;

        }

        return $salesforceObject;
    }
}
