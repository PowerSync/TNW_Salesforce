<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Transport\Soap\Calls\Upsert;

use TNW\Salesforce\Synchronize\Transport;

/**
 * Upsert Input
 */
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
     * Do Unit synchronization to Salesforce object
     *
     * @param Transport\Calls\Upsert\Transport\Input $input
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process(Transport\Calls\Upsert\Transport\Input $input)
    {
        $this->eventManager->dispatch('tnw_salesforce_call_upsert_before', ['input' => $input]);

        $maxPage = ceil($input->count() / self::BATCH_LIMIT);
        for ($input->rewind(), $i = 1; $i <= $maxPage; $i++) {
            $batch = $entities = $upsertIds = $duplicates = [];
            for (; $input->valid() && count($batch) <= self::BATCH_LIMIT; $input->next()) {
                $data = $input->getInfo();
                $entity = $input->current();

                // De duplicate
                if (isset($data['Id'])) {
                    $hasObject = array_search($data['Id'], $upsertIds, true);
                    if (false !== $hasObject) {
                        $duplicates[$hasObject][] = $entity;
                        continue;
                    }

                    $upsertIds[spl_object_hash($entity)] = $data['Id'];
                }

                $duplicates[spl_object_hash($entity)] = [];
                $batch[] = (object)$data;
                $entities[] = $entity;
            }

            $results = $this->factory->client()->upsert($input->externalIdFieldName(), $batch, $input->type());
            foreach ($entities as $key => $entity) {
                if (empty($results[$key])) {
                    continue;
                }

                $this->storage->saveResult($entity, $results[$key]);
                foreach ($duplicates[spl_object_hash($entity)] as $duplicate) {
                    $this->storage->saveResult($duplicate, $results[$key]);
                }
            }
        }
    }

    /**
     * Hash Object
     *
     * @param array $object
     * @return string|null
     */
    public function hashObject($object): ?string
    {
        return empty($object['Id'])
            ? null : $object['Id'];
    }
}
