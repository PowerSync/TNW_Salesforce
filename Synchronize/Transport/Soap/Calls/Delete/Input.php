<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Transport\Soap\Calls\Delete;

use Magento\Framework\Event\Manager;
use Magento\Framework\Exception\LocalizedException;
use TNW\Salesforce\Synchronize\Transport;

/**
 * Upsert Input
 */
class Input implements Transport\Calls\Delete\InputInterface
{
    const BATCH_LIMIT = 200;

    /**
     * @var Manager
     */
    private $eventManager;

    /**
     * @var Transport\Soap\Calls\Delete\Storage
     */
    private $storage;

    /**
     * @var Transport\Soap\ClientFactory
     */
    private $factory;

    /**
     * Soap constructor.
     * @param Manager $eventManager
     * @param Transport\Soap\Calls\Delete\Storage $storage
     * @param Transport\Soap\ClientFactory $factory
     */
    public function __construct(
        Manager $eventManager,
        Transport\Soap\Calls\Delete\Storage $storage,
        Transport\Soap\ClientFactory $factory
    )
    {
        $this->eventManager = $eventManager;
        $this->storage = $storage;
        $this->factory = $factory;
    }

    /**
     * Do Unit synchronization to Salesforce object
     *
     * @param Transport\Calls\Upsert\Transport\Input $input
     * @throws LocalizedException
     */
    public function process(Transport\Calls\Delete\Transport\Input $input)
    {
        $this->eventManager->dispatch('tnw_salesforce_call_delete_before', ['input' => $input]);

        $maxPage = ceil($input->count() / self::BATCH_LIMIT);
        for ($input->rewind(), $i = 1; $i <= $maxPage; $i++) {
            $batch = $entities = $upsertIds = $duplicates = [];
            for (; $input->valid() && count($batch) < self::BATCH_LIMIT; $input->next()) {
                $data = $input->getInfo();
                $entity = $input->current();

                // De duplicate
                if (isset($data['Id'])) {
                    $hasObject = array_search($data['Id'], $upsertIds, true);
                    if (false !== $hasObject) {
                        $duplicates[$hasObject][] = $entity;
                        continue;
                    }

                    $upsertIds[] = $data['Id'];
                }

                $duplicates[spl_object_hash($entity)] = [];
                $batch[] = (object)$data;
                $entities[] = $entity;
            }

            $results = $this->factory->client()->callDelete($upsertIds);
            foreach ($entities as $key => $entity) {
                if (empty($results[$key])) {
                    continue;
                }

                $this->storage->saveResult($entity->getData('_queue'), $results[$key]);
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
