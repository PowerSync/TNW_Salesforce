<?php

namespace TNW\Salesforce\Synchronize\Transport\Soap\Calls;

use TNW\Salesforce\Synchronize\Transport;

class Query implements Transport\Calls\QueryInterface
{
    const MAX_LENGTH = 20000;

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
     * @param Transport\Calls\Query\Input $input
     * @param Transport\Calls\Query\Output $output
     * @throws \RuntimeException
     */
    public function process(Transport\Calls\Query\Input $input, Transport\Calls\Query\Output $output)
    {
        $this->eventManager->dispatch('tnw_salesforce_call_query_before', [
            'input' => $input,
            'output' => $output
        ]);

        for ($input->rewind(); $input->valid(); /*unused*/) {
            $entities = [];
            $query = '';

            for (/*unused*/; $input->valid(); $input->next()) {
                $entities[] = $input->current();
                $testQuery = $input->query($entities);
                if (mb_strlen($testQuery) >= self::MAX_LENGTH) {
                    break;
                }

                $query = $testQuery;
            }

            if (empty($query)) {
                throw new \RuntimeException(sprintf('Query exceeded limit of %d characters', self::MAX_LENGTH));
            }

            $results = $this->factory->client()->query($query);
            foreach ($results as $result) {
                $output[] = $this->prepareOutput($result);
            }
        }

        $this->eventManager->dispatch('tnw_salesforce_call_query_after', [
            'input' => $input,
            'output' => $output
        ]);
    }

    /**
     * @param $result
     * @return array
     */
    protected function prepareOutput($result)
    {
        $return = [];
        foreach (get_object_vars($result) as $key => $value) {
            switch (true) {
                case $value instanceof \Tnw\SoapClient\Result\SObject:
                    $return[$key] = $this->prepareOutput($value);
                    break;

                case $value instanceof \Iterator:
                    foreach ($value as $item) {
                        $return[$key][] = $this->prepareOutput($item);
                    }
                    break;

                default:
                    $return[$key] = $value;
                    break;
            }
        }

        return $return;
    }
}