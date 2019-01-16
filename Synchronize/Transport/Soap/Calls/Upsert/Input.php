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

            $this->storage->setBatchByEntities($batch, $entities, $input->externalIdFieldName(), $input->type());
        }
    }
}
