<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Model\Config;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\App\Emulation;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Class Owner
 * @package TNW\Salesforce\Model\Config\Source\Customer
 */
class WebsiteEmulator
{

    /** @var Emulation */
    protected $storeEmulator;

    /** @var WebsiteDetector */
    protected $websiteDetector;

    /** @var State */
    protected $appState;

    /** @var ManagerInterface */
    private $messageManager;

    /** @var LoggerInterface */
    private $logger;

    /**
     * WebsiteEmulator constructor.
     * @param WebsiteDetector $websiteDetector
     * @param Emulation $storeEmulator
     * @param State $appState
     */
    public function __construct(
        WebsiteDetector $websiteDetector,
        Emulation $storeEmulator,
        State $appState,
        ManagerInterface $messageManager,
        LoggerInterface $logger

    ) {
        $this->websiteDetector = $websiteDetector;
        $this->storeEmulator = $storeEmulator;
        $this->appState = $appState;
        $this->messageManager = $messageManager;
        $this->logger = $logger;
    }

    /**
     * Emulate specific store
     *
     * @param int $websiteId
     * @throws LocalizedException
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
     * @return Emulation
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
     * @throws LocalizedException
     */
    public function execInWebsite($callback, $websiteId, $params = [])
    {
        $this->startEnvironmentEmulation($websiteId);

        $result = null;
        try {
            $result = $this->appState
                ->emulateAreaCode(
                    Area::AREA_FRONTEND,
                    $callback,
                    array_merge([$websiteId], $params)
                );
        } catch (Throwable $e) {
            $message = implode(PHP_EOL, [$e->getMessage(), $e->getTraceAsString()]);
            $this->logger->critical($message);
            if ($this->appState->getAreaCode() == Area::AREA_ADMINHTML) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
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
     * @throws LocalizedException
     */
    public function wrapEmulationWebsite($callback, $websiteId, $params = [])
    {
        return $this->execInWebsite($callback, $websiteId, $params);
    }
}
