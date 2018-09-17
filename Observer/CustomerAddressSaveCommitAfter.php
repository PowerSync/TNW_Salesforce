<?php
namespace TNW\Salesforce\Observer;

class CustomerAddressSaveCommitAfter implements \Magento\Framework\Event\ObserverInterface
{

    /**
     * @var Entities
     */
    private $entities;

    /**
     * @var \TNW\Salesforce\Model\Customer\Config
     */
    private $customerConfig;

    /**
     * AfterSaveCommitObserver constructor.
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
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Customer\Model\Address $address */
        $address = $observer->getEvent()->getData('data_object');

        $customer = $address->getCustomer();
        if (!$customer instanceof \Magento\Customer\Model\Customer || $customer->getId() === null) {
            return;
        }

        if (!$this->customerConfig->getSalesforceStatus($customer->getWebsiteId())) {
            return;
        }

        if (!$this->customerConfig->getCustomerStatus($customer->getWebsiteId())) {
            return;
        }

        if (
            !$this->customerConfig->getCustomerAllGroups($customer->getWebsiteId()) &&
            !in_array((int)$customer->getGroupId(), $this->customerConfig->getCustomerSyncGroups($customer->getWebsiteId()))
        ) {
            return;
        }

        $this->entities->addEntity($customer);
    }
}