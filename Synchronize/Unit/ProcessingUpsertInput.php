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
     * Analize
     *
     * @param \Magento\Framework\Model\AbstractModel $entity
     * @return bool|\Magento\Framework\Phrase
     */
    public function analize($entity)
    {
        /** @var \TNW\Salesforce\Model\Queue $queue */
        $queue = $this->load()->get('%s/queue', $entity);
        if ($queue->isProcessInputUpsert()) {
            return true;
        }

        return __('not upsert input phase');
    }
}
