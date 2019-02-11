<?php
namespace TNW\Salesforce\Synchronize\Unit;

/**
 * Processing Upsert Output
 */
class ProcessingUpsertOutput extends ProcessingAbstract
{
    /**
     * Analize
     *
     * @param \Magento\Framework\Model\AbstractModel $entity
     * @return bool|\Magento\Framework\Phrase
     */
    public function analize($entity)
    {
        /** @var \TNW\Salesforce\Model\Queue $queue */
        $queue = $this->load()->get('%s/queue', $entity);
        if ($queue->isUpsertOutput() || $queue->isUpsertWaiting()) {
            return true;
        }

        return __('not upsert output phase');
    }
}
