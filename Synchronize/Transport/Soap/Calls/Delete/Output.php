<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Synchronize\Transport\Soap\Calls\Delete;

use Magento\Framework\Event\Manager;
use TNW\Salesforce\Synchronize\Transport;
use Tnw\SoapClient\Result\Error;

/**
 * Upsert Output
 */
class Output implements Transport\Calls\Delete\OutputInterface
{
    /**
     * @var Manager
     */
    private $eventManager;

    /**
     * @var Storage
     */
    private $storage;

    /**
     * Soap constructor.
     * @param Manager $eventManager
     * @param Storage $storage
     */
    public function __construct(
        Manager $eventManager,
        Transport\Soap\Calls\Delete\Storage $storage
    )
    {
        $this->eventManager = $eventManager;
        $this->storage = $storage;
    }

    /**
     * @return Storage
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * @param Storage $storage
     */
    public function setStorage(Storage $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Do Unit syncronization to Salesforce object
     *
     * @param Transport\Calls\Upsert\Transport\Output $output
     */
    public function process(Transport\Calls\Delete\Transport\Output $output)
    {
        for ($output->rewind(); $output->valid(); $output->next()) {
            $result = $this->storage->searchResult($output->current()->getData('_queue'));
            if (null === $result) {
                $output->setInfo([
                    'skipped' => true,
                    'success' => false,
                    'created' => false,
                    'message' => ''
                ]);

                continue;
            }

            $output->setInfo([
                'salesforce' => $result->getId(),
                'success' => $result->isSuccess(),
                'message' => implode("\n", array_filter(array_map([$this, 'message'], (array)$result->getErrors())))
            ]);
        }

        $this->eventManager->dispatch('tnw_salesforce_call_upsert_after', ['output' => $output]);
    }

    /**
     * Message
     *
     * @param Error $error
     * @return string
     */
    public function message(Error $error)
    {
        $message = $error->getMessage();
        if (count($fields = (array)$error->getFields()) !== 0) {
            $message .= sprintf(', fields [%s]', implode(', ', $fields));
        }

        return $message;
    }
}
