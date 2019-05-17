<?php
namespace TNW\Salesforce\Controller\Adminhtml\System\Store;

/**
 * Class SyncAll
 */
class SyncAll extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Store\Api\WebsiteRepositoryInterface
     */
    private $websiteRepository;

    /**
     * @var \TNW\Salesforce\Synchronize\Queue\Add
     */
    private $entityWebsite;

    /**
     * SyncAll constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepository
     * @param \TNW\Salesforce\Synchronize\Queue\Add $entityWebsite
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepository,
        \TNW\Salesforce\Synchronize\Queue\Add $entityWebsite
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
        try {
            $this->entityWebsite->addToQueue(\array_map([$this, 'websiteId'], $this->websiteRepository->getList()));
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e);
        }

        return $this->resultRedirectFactory->create()
            ->setPath('adminhtml/*/');
    }

    /**
     * Website Id
     *
     * @param \Magento\Store\Api\Data\WebsiteInterface $website
     * @return mixed
     */
    private function websiteId($website)
    {
        return $website->getId();
    }
}
