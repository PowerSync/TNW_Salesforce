<?php
namespace TNW\Salesforce\Synchronize\Unit;

/**
 * Processing Upsert Output
 * @deprecated
 */
class ProcessingUpsertOutput extends ProcessingAbstract
{
    /**
     * @inheritdoc
     */
    public function description()
    {
        return __('Upsert Output Phase');
    }

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
        if ($queue->isProcessInputUpsert() || $queue->isProcessOutputUpsert()) {
            return true;
        }

        return __('not upsert output phase');
    }
}
