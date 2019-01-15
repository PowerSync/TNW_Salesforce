<?php
namespace TNW\Salesforce\Synchronize\Transport\Soap\Calls\Upsert;

use TNW\Salesforce\Synchronize\Transport;

class Output implements Transport\Calls\Upsert\OutputInterface
{
    const BATCH_LIMIT = 200;

    /**
     * @var \Magento\Framework\Event\Manager
     */
    private $eventManager;

    /**
     * Soap constructor.
     * @param \Magento\Framework\Event\Manager $eventManager
     */
    public function __construct(
        \Magento\Framework\Event\Manager $eventManager
    ) {
        $this->eventManager = $eventManager;
    }

    /**
     * Do Unit syncronization to Salesforce object
     *
     * @param Transport\Calls\Upsert\Output $output
     */
    public function process(Transport\Calls\Upsert\Output $output)
    {
        $this->eventManager->dispatch('tnw_salesforce_call_upsert_output_before', ['output' => $output]);

        //TODO: get
        $entities = [];
        $results = [];

        foreach ($entities as $key => $entity) {
            if (empty($results[$key])) {
                continue;
            }

            $output[$entity] = [
                'salesforce' => $results[$key]->getId(),
                'success' => $results[$key]->isSuccess(),
                'created' => $results[$key]->isCreated(),
                'message' => implode("\n", array_filter(array_map([$this, 'message'], (array)$results[$key]->getErrors())))
            ];
        }

        $this->eventManager->dispatch('tnw_salesforce_call_upsert_output_after', ['output' => $output]);
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
