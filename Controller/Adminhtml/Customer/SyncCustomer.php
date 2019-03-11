<?php
namespace TNW\Salesforce\Controller\Adminhtml\Customer;

class SyncCustomer extends \Magento\Backend\App\Action
{
    /**
     * @var \TNW\Salesforce\Synchronize\Queue\Add
     */
    private $entityCustomer;

    /**
     * SyncCustomer constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \TNW\Salesforce\Synchronize\Queue\Add $entityCustomer
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \TNW\Salesforce\Synchronize\Queue\Add $entityCustomer
    ) {
        $this->entityCustomer = $entityCustomer;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $customerId = $this->getRequest()->getParam('customer_id');
        try {
            $this->entityCustomer->addToQueue([$customerId]);
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e);
        }

        $return = $this->getRequest()->getParam('return', 'edit');
        if (strcasecmp($return, 'index') === 0) {
            return $this->resultRedirectFactory->create()
                ->setPath('customer/index');
        }

        return $this->resultRedirectFactory->create()
            ->setPath('customer/index/edit', ['id' => $customerId]);
    }
}
