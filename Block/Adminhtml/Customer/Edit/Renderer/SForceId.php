<?php
declare(strict_types=1);

namespace TNW\Salesforce\Block\Adminhtml\Customer\Edit\Renderer;

use Magento\Customer\Controller\RegistryConstants;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class SForceId
 * @package TNW\Salesforce\Block\Adminhtml\Customer\Edit\Renderer
 */
class SForceId extends \TNW\Salesforce\Block\Adminhtml\Base\Edit\Renderer\SForceId
{

    /**
     * @return integer
     */
    public function getEntityId(): int
    {
        return $this->registry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
    }

    /**
     * @return string
     */
    public function getMagentoObjectType(): string
    {
        return \TNW\Salesforce\Model\Entity\SalesforceIdStorage::MAGENTO_TYPE_CUSTOMER;
    }

    /**
     * @return null|string
     * @throws LocalizedException
     */
    public function getSalesforceObjectByAttribute(): ?string
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
                throw new LocalizedException(__('Unknown attribute: %1', $this->getId()));
        }

        return $salesforceObject;
    }
}
