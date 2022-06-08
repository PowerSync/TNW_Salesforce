<?php
namespace TNW\Salesforce\Synchronize\Transport\Soap\Calls\Upsert;

use TNW\Salesforce\Synchronize\Transport;
use Tnw\SoapClient\Result\Error;

/**
 * Upsert Output
 */
class Output implements Transport\Calls\Upsert\OutputInterface
{
    /**
     * @var \Magento\Framework\Event\Manager
     */
    private $eventManager;

    /**
     * @var Storage
     */
    private $storage;

    /**
     * Soap constructor.
     * @param \Magento\Framework\Event\Manager $eventManager
     * @param Storage $storage
     */
    public function __construct(
        \Magento\Framework\Event\Manager $eventManager,
        Transport\Soap\Calls\Upsert\Storage $storage
    ) {
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
    public function process(Transport\Calls\Upsert\Transport\Output $output)
    {
        for ($output->rewind(); $output->valid(); $output->next()) {
            $result = $this->storage->searchResult($output->current());
            if (null === $result) {
                $output->setInfo([
                    'skipped' => true,
                    'success' => false,
                    'created' => false,
                    'message' => '',
                    'status_code' => null
                ]);

                continue;
            }

            $output->setInfo([
                'salesforce' => $result->getId(),
                'success' => $result->isSuccess(),
                'created' => $result->isCreated(),
                'message' => implode("\n", array_filter(array_map([$this, 'message'], (array)$result->getErrors()))),
                'status_code' => implode(PHP_EOL, array_filter(array_map([$this, 'statusCode'], (array)$result->getErrors())))
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

    /**
     * Status code
     *
     * @param Error $error
     * @return string
     */
    public function statusCode(Error $error): string
    {
        return (string)$error->getStatusCode();
    }
}
