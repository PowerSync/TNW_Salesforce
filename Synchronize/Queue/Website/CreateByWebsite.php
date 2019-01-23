<?php
namespace TNW\Salesforce\Synchronize\Queue\Website;

use Magento\Framework\Exception\NoSuchEntityException;

class CreateByWebsite implements \TNW\Salesforce\Synchronize\Queue\CreateInterface
{
    /**
     * @var \Magento\Store\Model\WebsiteRepository
     */
    private $websiteRepository;

    /**
     * CreateByWebsite constructor.
     * @param \Magento\Store\Model\WebsiteRepository $websiteRepository
     */
    public function __construct(
        \Magento\Store\Model\WebsiteRepository $websiteRepository
    ) {
        $this->websiteRepository = $websiteRepository;
    }

    /**
     * @param int $entityId
     * @param callable $create
     * @return mixed
     */
    public function process($entityId, callable $create)
    {
        try {
            $this->websiteRepository->getById($entityId);
            return [$create('website', $entityId)];
        } catch (NoSuchEntityException $e) {
            return [];
        }
    }
}
