<?php

namespace TNW\Salesforce\Synchronize\Transport\Soap\Calls;

use TNW\Salesforce\Synchronize\Transport;

class Upsert implements Transport\Calls\UpsertInterface
{

    const BATCH_LIMIT = 200;

    /**
     * @var \Magento\Framework\Event\Manager
     */
    private $eventManager;

    /**
     * @var \TNW\Salesforce\Synchronize\Transport\Soap\ClientFactory
     */
    private $factory;

    /**
     * Soap constructor.
     * @param \Magento\Framework\Event\Manager $eventManager
     * @param \TNW\Salesforce\Synchronize\Transport\Soap\ClientFactory $factory
     */
    public function __construct(
        \Magento\Framework\Event\Manager $eventManager,
        \TNW\Salesforce\Synchronize\Transport\Soap\ClientFactory $factory
    ) {
        $this->eventManager = $eventManager;
        $this->factory = $factory;
    }

    /**
     * Do Unit syncronization to Salesforce object
     *
     * @param Transport\Calls\Upsert\Input $input
     * @param Transport\Calls\Upsert\Output $output
     */
    public function process(Transport\Calls\Upsert\Input $input, Transport\Calls\Upsert\Output $output)
    {
        $this->eventManager->dispatch('tnw_salesforce_call_upsert_before', [
            'input' => $input,
            'output' => $output
        ]);

        $maxPage = ceil($input->count() / self::BATCH_LIMIT);
        for ($input->rewind(), $i = 1; $i <= $maxPage; $i++) {
            $batch = $entities = [];
            for (; $input->valid() && count($batch) <= self::BATCH_LIMIT; $input->next()) {
                $batch[] = (object)$input->getInfo();
                $entities[] = $input->current();
            }

            /** @var \Tnw\SoapClient\Result\UpsertResult[] $results */
            $results = $this->factory->client()
                ->upsert($input->externalIdFieldName(), $batch, $input->type());

            foreach ($entities as $key => $entity) {
                if (empty($results[$key])) {
                    continue;
                }

                $output[$entity] = [
                    'salesforce' => $results[$key]->getId(),
                    'success' => $results[$key]->isSuccess(),
                    'created' => $results[$key]->isCreated(),
                    'message' => implode("\n", array_filter(array_map(function (\Tnw\SoapClient\Result\Error $error) {
                        $message = $error->getMessage();
                        if (count($fields = (array)$error->getFields()) !== 0) {
                            $message .= sprintf(', fields [%s]', implode(', ', $fields));
                        }

                        return $message;
                    }, (array)$results[$key]->getErrors())))
                ];
            }
        }

        $this->eventManager->dispatch('tnw_salesforce_call_upsert_after', [
            'input' => $input,
            'output' => $output
        ]);
    }
}