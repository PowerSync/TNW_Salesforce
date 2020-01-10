<?php

namespace TNW\Salesforce\Synchronize\Unit;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Phrase;
use TNW\Salesforce\Model\Config;
use TNW\Salesforce\Model\Queue;

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
     * @param AbstractModel $entity
     * @return bool|Phrase
     */
    public function analize($entity)
    {
        /** @var Queue $queue */
        $queue = $this->load()->get('%s/queue', $entity);
        if (($queue->isProcessInputUpsert() && (int)$queue->getSyncType() === Config::DIRECT_SYNC_TYPE_REALTIME) || $queue->isProcessOutputUpsert()) {
            return true;
        }

        return __('not upsert output phase');
    }
}
