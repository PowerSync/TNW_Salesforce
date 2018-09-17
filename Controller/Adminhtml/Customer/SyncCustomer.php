<?php
namespace TNW\Salesforce\Controller\Adminhtml\Customer;

/**
 * Class SyncCustomer
 * @package TNW\Salesforce\Controller\Adminhtml\Customer
 */
class SyncCustomer extends \Magento\Backend\App\Action
{
    /**
     * @var \TNW\Salesforce\Synchronize\Entity
     */
    private $entityCustomer;

    /**
     * SyncCustomer constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \TNW\Salesforce\Synchronize\Entity $entityCustomer
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \TNW\Salesforce\Synchronize\Entity $entityCustomer
    ) {
        $this->entityCustomer = $entityCustomer;
        parent::__construct($context);
    }

    public function execute()
    {
        $customerId = $this->getRequest()->getParam('customer_id');
        $this->entityCustomer->synchronize([$customerId]);

        $return = $this->getRequest()->getParam('return', 'edit');
        if ($return == 'index') {
            return $this->resultRedirectFactory->create()
                ->setPath('customer/index');
        }

        return $this->resultRedirectFactory->create()
            ->setPath('customer/index/edit', ['id' => $customerId]);
    }
}
