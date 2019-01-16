<?php
namespace TNW\Salesforce\Synchronize\Transport\Soap\Calls\Upsert;

use TNW\Salesforce\Synchronize\Transport;

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
     * Do Unit syncronization to Salesforce object
     *
     * @param Transport\Calls\Upsert\Output $output
     */
    public function process(Transport\Calls\Upsert\Output $output)
    {
        for ($output->rewind(); $output->valid(); $output->next()) {
            $result = $this->storage->searchResult($output->current());
            if (null === $result) {
                continue;
            }

            $output->setInfo([
                'salesforce' => $result->getId(),
                'success' => $result->isSuccess(),
                'created' => $result->isCreated(),
                'message' => implode("\n", array_filter(array_map(
                    [$this, 'message'],
                    (array)$result->getErrors()
                )))
            ]);
        }

        $this->storage->clear();
        $this->eventManager->dispatch('tnw_salesforce_call_upsert_after', ['output' => $output]);
    }

    /**
     * @param \Tnw\SoapClient\Result\Error $error
     * @return string
     */
    public function message(\Tnw\SoapClient\Result\Error $error)
    {
        $message = $error->getMessage();
        if (count($fields = (array)$error->getFields()) !== 0) {
            $message .= sprintf(', fields [%s]', implode(', ', $fields));
        }

        return $message;
    }
}
