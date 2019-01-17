<?php
namespace TNW\Salesforce\Synchronize\Transport\Soap\Calls\Upsert;

use TNW\Salesforce\Synchronize\Transport;

class Input implements Transport\Calls\Upsert\InputInterface
{
    const BATCH_LIMIT = 200;

    /**
     * @var \Magento\Framework\Event\Manager
     */
    private $eventManager;

    /**
     * @var Transport\Soap\Calls\Upsert\Storage
     */
    private $storage;

    /**
     * @var Transport\Soap\ClientFactory
     */
    private $factory;

    /**
     * Soap constructor.
     * @param \Magento\Framework\Event\Manager $eventManager
     * @param Transport\Soap\Calls\Upsert\Storage $storage
     * @param Transport\Soap\ClientFactory $factory
     */
    public function __construct(
        \Magento\Framework\Event\Manager $eventManager,
        Transport\Soap\Calls\Upsert\Storage $storage,
        Transport\Soap\ClientFactory $factory
    ) {
        $this->eventManager = $eventManager;
        $this->storage = $storage;
        $this->factory = $factory;
    }

    /**
     * Do Unit syncronization to Salesforce object
     *
     * @param Transport\Calls\Upsert\Input $input
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process(Transport\Calls\Upsert\Input $input)
    {
        $this->eventManager->dispatch('tnw_salesforce_call_upsert_before', ['input' => $input]);

        $maxPage = ceil($input->count() / self::BATCH_LIMIT);
        for ($input->rewind(), $i = 1; $i <= $maxPage; $i++) {
            $batch = $entities = [];
            for (; $input->valid() && count($batch) <= self::BATCH_LIMIT; $input->next()) {
                $batch[] = (object)$input->getInfo();
                $entities[] = $input->current();
            }

            $results = $this->factory->client()->upsert($input->externalIdFieldName(), $batch, $input->type());
            foreach ($entities as $key => $entity) {
                if (empty($results[$key])) {
                    continue;
                }

                $this->storage->saveResult($entity, $results[$key]);
            }
        }
    }
}
