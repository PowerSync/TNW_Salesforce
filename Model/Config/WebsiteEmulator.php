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

    /**
     * WebsiteEmulator constructor.
     * @param WebsiteDetector $websiteDetector
     * @param \Magento\Store\Model\App\Emulation $storeEmulator
     */
    public function __construct(
        WebsiteDetector $websiteDetector,
        \Magento\Store\Model\App\Emulation $storeEmulator
    )
    {
        $this->websiteDetector = $websiteDetector;
        $this->storeEmulator = $storeEmulator;;
    }

    /**
     * emulate specific store
     *
     * @param null $websiteId
     * @return bool
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
            return false;
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
    public function wrapEmulationWebsite($callback, $websiteId = null)
    {
        $this->startEnvironmentEmulation($websiteId);

        try {
            $return = call_user_func($callback);
        } catch (\Exception $e) {
            $this->stopEnvironmentEmulation();
            throw $e;
        }

        $this->stopEnvironmentEmulation();
        return $return;
    }
}