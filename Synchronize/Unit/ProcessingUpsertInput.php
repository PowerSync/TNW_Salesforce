<?php
namespace TNW\Salesforce\Synchronize\Unit;

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
     * @param $entity
     * @return bool
     */
    public function isEntityEmpty($entity)
    {
        /**
         * remove technical data
         */
        $data = $entity->getData();
        unset($data['config_website']);
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
     * @param \Magento\Framework\Model\AbstractModel $entity
     * @return bool|\Magento\Framework\Phrase
     * @throws \Exception
     */
    public function analize($entity)
    {
        /** @var \TNW\Salesforce\Model\Queue $queue */
        $queue = $this->load()->get('%s/queue', $entity);

        if ($this->isEntityEmpty($entity)) {
            throw new \Exception(__('The entity related to the queue record #%1 is not available anymore', $queue->getId()));
        }

        if ($queue->isProcessInputUpsert()) {
            return true;
        }

        return __('not upsert input phase');
    }
}
