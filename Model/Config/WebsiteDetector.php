<?php

namespace TNW\Salesforce\Model\Config;

/**
 * Class Owner
 * @package TNW\Salesforce\Model\Config\Source\Customer
 */
class WebsiteDetector
{

    /** @var \Magento\Store\Model\App\Emulation */
    protected $storeEmulator;

    /** @var \Magento\Store\Model\StoreManagerInterface  */
    protected $storeManager;

    /**
     * Request object
     *
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\Store\Model\WebsiteFactory
     */
    protected $websiteFactory;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * WebsiteEmulator constructor.
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Store\Model\App\Emulation $storeEmulator
     * @param \Magento\Store\Model\WebsiteFactory $websiteFactory
     * @param \Magento\Framework\App\State $appState
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Store\Model\App\Emulation $storeEmulator,
        \Magento\Store\Model\WebsiteFactory $websiteFactory,
        \Magento\Framework\App\State $appState,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    )
    {
        $this->request = $request;
        $this->storeEmulator = $storeEmulator;
        $this->websiteFactory = $websiteFactory;
        $this->appState = $appState;
        $this->storeManager = $storeManager;
    }

    /**
     * @return mixed
     */
    public function getWebsiteFromRequest()
    {
        $websiteId = $this->request->getParam(
            'website'
        );

        return $websiteId;
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isAdminArea()
    {
        return ($this->appState->getAreaCode() == \Magento\Framework\App\Area::AREA_ADMINHTML);
    }

    /**
     * @param $websiteId int
     * @return int|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function detectCurrentWebsite($websiteId = null)
    {
        if (empty($websiteId)) {
            $websiteId = ($this->isAdminArea()) ? $this->getWebsiteFromRequest() : $this->storeManager->getWebsite()->getId();
        }

        return $websiteId;
    }

    /**
     * @param null $websiteId
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getStroreIdByWebsite($websiteId = null)
    {
        $websiteId = $this->detectCurrentWebsite($websiteId);

        $website = $this->websiteFactory->create()->load($websiteId);

        $website->getStoreId();

        return $website->getStoreId();
    }

//
//    /**
//     * emulate specific store
//     *
//     * @param null $websiteId
//     * @throws \Magento\Framework\Exception\LocalizedException
//     */
//    public function startEnvironmentEmulation($websiteId = null)
//    {
//        $storeId = $this->getStroreIdByWebsite($websiteId);
//
//        $this->storeEmulator->startEnvironmentEmulation($storeId);
//    }
//
//    /**
//     * @return \Magento\Store\Model\App\Emulation
//     */
//    public function stopEnvironmentEmulation()
//    {
//        return $this->storeEmulator->stopEnvironmentEmulation();
//    }
//
//
//    /**
//     * @param $callback
//     * @param null $websiteId
//     * @return mixed
//     * @throws \Magento\Framework\Exception\LocalizedException
//     */
//    public function wrapEmulationWebsite($callback, $websiteId = null)
//    {
//        $this->startEnvironmentEmulation($websiteId);
//
//        try {
//            $return = call_user_func($callback);
//        } catch (\Exception $e) {
//            $this->stopEnvironmentEmulation();
//            throw $e;
//        }
//
//        $this->stopEnvironmentEmulation();
//        return $return;
//    }
}