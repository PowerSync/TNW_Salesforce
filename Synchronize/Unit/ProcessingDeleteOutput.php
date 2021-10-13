<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Unit;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Phrase;
use TNW\Salesforce\Model\Queue;

/**
 * Processing Upsert Output
 * @deprecated
 */
class ProcessingDeleteOutput extends ProcessingAbstract
{
    /**
     * @inheritdoc
     */
    public function description(): Phrase
    {
        return __('Delete Output Phase');
    }

    /**
     * Analize
     *
     * @param AbstractModel $entity
     * @return bool|Phrase
     */
    public function analize($entity)
    {
        /** @var Queue $queue */
        $queue = $this->load()->get('%s/queue', $entity);

        if (($queue->isProcessInputUpsert()) || $queue->isProcessOutputUpsert()) {
            return true;
        }

        return __('not delete output phase');
    }
}
