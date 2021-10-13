<?php
declare(strict_types=1);

namespace TNW\Salesforce\Controller\Adminhtml\Customer;

/**
 * MassSync
 */
class MassSync extends \Magento\Backend\App\Action
{
    /**
     * @var \TNW\Salesforce\Synchronize\Queue\Add
     */
    private $entityCustomer;

    /**
     * @var \Magento\Ui\Component\MassAction\Filter
     */
    private $massActionFilter;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    private $collectionFactory;

    /**
     * MassSync constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \TNW\Salesforce\Synchronize\Queue\Add $entityCustomer
     * @param \Magento\Ui\Component\MassAction\Filter $massActionFilter
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $collectionFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \TNW\Salesforce\Synchronize\Queue\Add $entityCustomer,
        \Magento\Ui\Component\MassAction\Filter $massActionFilter,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
        $this->entityCustomer = $entityCustomer;
        $this->massActionFilter = $massActionFilter;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute(): \Magento\Framework\Controller\Result\Redirect
    {
        try {
            $entityIds = $this->massActionFilter
                ->getCollection($this->collectionFactory->create())
                ->getAllIds();

            $this->entityCustomer->addToQueue($entityIds);
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e);
        }

        return $this->resultRedirectFactory->create()
            ->setPath('customer/index');
    }
}
