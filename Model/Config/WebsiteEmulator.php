<?php

namespace TNW\Salesforce\Model\Config;

/**
 * Class Owner
 * @package TNW\Salesforce\Model\Config\Source\Customer
 */
class WebsiteEmulator
{

    /** @var \Magento\Store\Model\App\Emulation */
    protected $storeEmulator;

    /** @var WebsiteDetector */
    protected $websiteDetector;

    /** @var \Magento\Framework\App\State */
    protected $appState;

    /**
     * WebsiteEmulator constructor.
     * @param WebsiteDetector $websiteDetector
     * @param \Magento\Store\Model\App\Emulation $storeEmulator
     * @param \Magento\Framework\App\State $appState
     */
    public function __construct(
        WebsiteDetector $websiteDetector,
        \Magento\Store\Model\App\Emulation $storeEmulator,
        \Magento\Framework\App\State $appState
    )
    {
        $this->websiteDetector = $websiteDetector;
        $this->storeEmulator = $storeEmulator;
        $this->appState = $appState;
    }

    /**
     * emulate specific store
     *
     * @param null $websiteId
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function startEnvironmentEmulation($websiteId = null)
    {

        if (
            $this->websiteDetector->getCurrentStoreWebsite()->getId() ==
            $this->websiteDetector->detectCurrentWebsite($websiteId)
        ) {
            /**
             * we in the necessary website already
             */
            return;
        }

        $storeId = $this->websiteDetector->getStroreIdByWebsite($websiteId);

        $this->storeEmulator->startEnvironmentEmulation($storeId);
    }

    /**
     * @return \Magento\Store\Model\App\Emulation
     */
    public function stopEnvironmentEmulation()
    {
        return $this->storeEmulator->stopEnvironmentEmulation();
    }

    /**
     * @param $callback
     * @param null $websiteId
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execInWebsite($callback, $websiteId = null)
    {
        $this->startEnvironmentEmulation($websiteId);

        try {
            $result = $callback($websiteId);
        } finally {
            $this->stopEnvironmentEmulation();
        }

        return $result;
    }

    /**
     * @param $callback
     * @param null $websiteId
     * @return mixed
     * @throws \Exception
     */
    public function wrapEmulationWebsite($callback, $websiteId = null)
    {
        return $this->appState
            ->emulateAreaCode(
                \Magento\Framework\App\Area::AREA_FRONTEND,
                [$this, "execInWebsite"],
                [$callback, $websiteId]
            );
    }
}
