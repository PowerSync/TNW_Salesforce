<?php
namespace TNW\Salesforce\Plugin\Synchronize\Unit;

class LoadAbstract
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * LoadByAbstract constructor.
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(\Magento\Store\Model\StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
    }

    /**
     * @param \TNW\Salesforce\Synchronize\Unit\LoadAbstract $subject
     * @param callable $process
     * @param \Magento\Framework\Model\AbstractModel $entity
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundWebsiteId(
        \TNW\Salesforce\Synchronize\Unit\LoadAbstract $subject,
        callable $process,
        $entity
    ) {
        return $this->storeManager->getWebsite()->getId();
    }
}
