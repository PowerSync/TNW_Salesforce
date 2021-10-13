<?php
declare(strict_types=1);

namespace TNW\Salesforce\Observer;

/**
 * Customer Save Commit After
 */
class CustomerSaveCommitAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \TNW\Salesforce\Observer\Entities
     */
    private $entities;

    /**
     * @var \TNW\Salesforce\Model\Customer\Config
     */
    private $customerConfig;

    /**
     * AfterSaveCommitObserver constructor.
     * @param \TNW\Salesforce\Observer\Entities $entities
     * @param \TNW\Salesforce\Model\Customer\Config $customerConfig
     */
    public function __construct(
        \TNW\Salesforce\Observer\Entities $entities,
        \TNW\Salesforce\Model\Customer\Config $customerConfig
    ) {
        $this->entities = $entities;
        $this->customerConfig = $customerConfig;
    }

    /**
     * Execute
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Customer\Model\Customer $website */
        $customer = $observer->getEvent()->getData('data_object');
        if (!$this->customerConfig->getSalesforceStatus($customer->getWebsiteId())) {
            return;
        }

        if (!$this->customerConfig->getCustomerStatus($customer->getWebsiteId())) {
            return;
        }

        $customerSyncGroups = $this->customerConfig->getCustomerSyncGroups($customer->getWebsiteId());
        if (!$this->customerConfig->getCustomerAllGroups($customer->getWebsiteId()) &&
            !in_array((int)$customer->getGroupId(), $customerSyncGroups)
        ) {
            return;
        }

        $this->entities->addEntity($customer);
    }
}
