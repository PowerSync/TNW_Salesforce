<?php
namespace TNW\Salesforce\Synchronize\Unit;

use Exception;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Phrase;
use TNW\Salesforce\Model\Queue;

/**
 * Processing Upsert Input
 */
class ProcessingUpsertInput extends ProcessingAbstract
{
    /**
     * @inheritdoc
     */
    public function description()
    {
        return __('Upsert Input Phase');
    }

    /**
     * @param AbstractModel $entity
     * @return bool
     */
    public function isEntityEmpty($entity)
    {
        /**
         * remove technical data
         */
        $data = $entity->getData();

        unset($data['config_website']);
        unset($data['product_options']);
        unset($data['_queue']);

        /**
         * check if no actual data is here
         */
        if (empty($data)) {
            return true;
        }

        return false;
    }

    /**
     * Analize
     *
     * @param AbstractModel $entity
     * @return bool|Phrase
     * @throws Exception
     */
    public function analize($entity)
    {
        /** @var Queue $queue */
        $queue = $this->load()->get('%s/queue', $entity);

        if ($this->isEntityEmpty($entity)) {
            return __('The entity related to the queue record #%1 is not available anymore', $queue->getId());
        }

        if ($queue->isProcessInputUpsert()) {
            return true;
        }

        return __('not upsert input phase');
    }
}
