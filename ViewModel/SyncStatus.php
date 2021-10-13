<?php
declare(strict_types=1);

namespace TNW\Salesforce\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use TNW\Salesforce\Model\Objects\Status\Options;

class SyncStatus implements ArgumentInterface
{
    /**
     * Generate status html
     *
     * @param int $status
     * @return string
     */
    public function getStatusHtml(int $status): string
    {
        switch ($status) {
            case Options::STATUS_IN_SYNC:
                $type = '-success success';
                $title = __(Options::LABEL_IN_SYNC);
                break;
            case Options::STATUS_IN_SYNC_PENDING:
            case Options::STATUS_OUT_OF_SYNC_PENDING:
                $type = '-pending pending';
                $title = __(Options::LABEL_PENDING);
                break;
            default:
                $type = '-warning error';
                $title = __(Options::LABEL_OUT_OF_SYNC);
                break;
        }

        return '<div class="message message' . $type . ' sync-status-salesforce" title="' . $title . '"></div>';
    }
}
