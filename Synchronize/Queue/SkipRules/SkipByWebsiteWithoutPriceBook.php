<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Queue\SkipRules;

use Magento\Store\Model\StoreManagerInterface;
use TNW\Salesforce\Model\Queue;
use TNW\Salesforce\Synchronize\Queue\SkipInterface;

class SkipByWebsiteWithoutPriceBook implements SkipInterface
{
    /** @var StoreManagerInterface */
    private $storeManager;

    public function __construct(
        StoreManagerInterface $storeManager
    )
    {
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritDoc
     */
    public function apply(Queue $queue): bool
    {
        $needSkip = false;
        $websiteId = $queue->getWebsiteId();
        if ($websiteId) {
            $website = $this->storeManager->getWebsite($websiteId);
            if($website && !$website->getData('default_pricebook')) {
                $needSkip = true;
            }
        }

        return $needSkip;
    }
}
