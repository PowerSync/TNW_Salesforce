<?php

namespace TNW\Salesforce\Controller\Adminhtml\Customer;

/**
 * Class MassSync
 * @package Magento\Customer\Controller\Adminhtml\Index
 */
class MassSync extends \Magento\Backend\App\Action
{

    /**
     * @var \TNW\Salesforce\Synchronize\Entity
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
     * @param \TNW\Salesforce\Synchronize\Entity $entityCustomer
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \TNW\Salesforce\Synchronize\Entity $entityCustomer,
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
     * @return \Magento\Framework\Controller\ResultInterface|\Magento\Framework\App\ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $entityIds = $this->massActionFilter
            ->getCollection($this->collectionFactory->create())
            ->getAllIds();

        $this->entityCustomer->synchronize($entityIds);

        return $this->resultRedirectFactory->create()
            ->setPath('customer/index');
    }
}
