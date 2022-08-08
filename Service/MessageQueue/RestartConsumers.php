<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\MessageQueue;

use Magento\Framework\MessageQueue\PoisonPill\PoisonPillPutInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 *  Put poison pill and restart consumers
 */
class RestartConsumers
{
    /** @var PoisonPillPutInterface */
    private $pillPut;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param PoisonPillPutInterface $pillPut
     * @param LoggerInterface        $logger
     */
    public function __construct(
        PoisonPillPutInterface $pillPut,
        LoggerInterface        $logger
    ) {
        $this->pillPut = $pillPut;
        $this->logger = $logger;
    }

    /**
     * Put poison pill
     *
     * @return void
     * @throws Throwable
     */
    public function execute(): void
    {
        try {
            $this->pillPut->put();
        } catch (Throwable $e) {
            $this->logger->critical($e->getMessage());
            throw $e;
        }
    }
}
