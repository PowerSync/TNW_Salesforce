<?php
namespace TNW\Salesforce\Controller\Adminhtml\System\Store;

class SyncAll extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Store\Api\WebsiteRepositoryInterface
     */
    private $websiteRepository;

    /**
     * @var \TNW\Salesforce\Synchronize\Entity
     */
    private $entityWebsite;

    /**
     * SyncAll constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepository
     * @param \TNW\Salesforce\Synchronize\Entity $entityWebsite
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepository,
        \TNW\Salesforce\Synchronize\Entity $entityWebsite
    ) {
        $this->websiteRepository = $websiteRepository;
        $this->entityWebsite = $entityWebsite;
        parent::__construct($context);
    }

    /**
     * Sync all available Websites
     *
     * @return \Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $this->entityWebsite->synchronize($this->websiteRepository->getList());

        return $this->resultRedirectFactory->create()
            ->setPath('adminhtml/*/');
    }
}
