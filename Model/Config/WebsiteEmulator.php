<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Model\Config;

use Psr\Log\LoggerInterface;

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

    /** @var LoggerInterface */
    private $logger;

    /**
     * WebsiteEmulator constructor.
     * @param WebsiteDetector $websiteDetector
     * @param \Magento\Store\Model\App\Emulation $storeEmulator
     * @param \Magento\Framework\App\State $appState
     */
    public function __construct(
        WebsiteDetector $websiteDetector,
        \Magento\Store\Model\App\Emulation $storeEmulator,
        \Magento\Framework\App\State $appState,
        LoggerInterface $logger
    ) {
        $this->websiteDetector = $websiteDetector;
        $this->storeEmulator = $storeEmulator;
        $this->appState = $appState;
        $this->logger = $logger;
    }

    /**
     * Emulate specific store
     *
     * @param int $websiteId
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function startEnvironmentEmulation($websiteId)
    {
        if ($this->websiteDetector->getCurrentStoreWebsite()->getId() ==
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
     * Execute In Website
     *
     * @param callable $callback
     * @param int $websiteId
     * @param array $params
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execInWebsite($callback, $websiteId, $params = [])
    {
        $this->startEnvironmentEmulation($websiteId);

        try {
            $result = $this->appState
                ->emulateAreaCode(
                    \Magento\Framework\App\Area::AREA_FRONTEND,
                    $callback,
                    array_merge([$websiteId], $params)
                );

        } catch (\Throwable $e) {
            $message = [
                $e->getMessage(),
                $e->getTraceAsString()
            ];
            $this->logger->critical(implode(PHP_EOL, $message));
        } finally {
            $this->stopEnvironmentEmulation();
        }

        return $result;
    }

    /**
     * Wrap Emulation Website
     *
     * @param callable $callback
     * @param int $websiteId
     * @param array $params
     * @return mixed
     * @see execInWebsite
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function wrapEmulationWebsite($callback, $websiteId, $params = [])
    {
        return $this->execInWebsite($callback, $websiteId, $params);
    }
}
