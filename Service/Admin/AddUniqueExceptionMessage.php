<?php
declare(strict_types=1);

/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Service\Admin;

use Exception;
use Magento\Framework\Debug;
use Magento\Framework\Message\ExceptionMessageFactoryInterface;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use TNW\Salesforce\Api\Service\Admin\AddUniqueExceptionMessageInterface;

/**
 * Add unique exception message to admin messages.
 */
class AddUniqueExceptionMessage implements AddUniqueExceptionMessageInterface
{
    /** @var ManagerInterface */
    private $messageManager;

    /** @var ExceptionMessageFactoryInterface */
    private $exceptionMessageFactory;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param ManagerInterface                 $messageManager
     * @param ExceptionMessageFactoryInterface $exceptionMessageFactory
     * @param LoggerInterface                  $logger
     */
    public function __construct(
        ExceptionMessageFactoryInterface $exceptionMessageFactory,
        ManagerInterface $messageManager,
        LoggerInterface $logger
    ) {
        $this->messageManager = $messageManager;
        $this->exceptionMessageFactory = $exceptionMessageFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function execute(Throwable $exception, bool $logException = true): void
    {
        $exception = $this->prepareException($exception);
        $messageObject = $this->exceptionMessageFactory->createMessage($exception);
        $this->messageManager->addUniqueMessages([$messageObject]);

        $logException && $this->logException($exception);
    }

    /**
     * @param Throwable $exception
     *
     * @return Exception
     */
    private function prepareException(Throwable $exception): Exception
    {
        if ($exception instanceof Exception) {
            return $exception;
        }

        return new Exception($exception->getMessage());
    }

    /**
     * @param Throwable $exception
     */
    private function logException(Throwable $exception): void
    {
        $message = sprintf(
            'Exception message: %s%sTrace: %s',
            $exception->getMessage(),
            PHP_EOL,
            Debug::trace(
                $exception->getTrace(),
                true,
                true,
                (bool)getenv('MAGE_DEBUG_SHOW_ARGS')
            )
        );

        $this->logger->critical($message);
    }
}
