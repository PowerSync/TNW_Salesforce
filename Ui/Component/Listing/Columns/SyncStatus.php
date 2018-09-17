<?php
namespace TNW\Salesforce\Ui\Component\Listing\Columns;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class SyncStatus
 * @package TNW\Salesforce\Ui\Component\Listing\Columns
 */
class SyncStatus extends Column
{
    /** @var  StoreManagerInterface */
    protected $storeManager;

    /**
     * SyncStatus constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param StoreManagerInterface $storeManagerInterface
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        StoreManagerInterface $storeManagerInterface,
        array $components,
        array $data
    ) {
        $this->storeManager = $storeManagerInterface;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        /** @var string $syncStatusName */
        $syncStatusName = 'sforce_sync_status';

        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {

                /** @var string $type */
                $type = '-warning error';
                $title = __('Out of Sync');

                if (key_exists($syncStatusName, $item)) {
                    $value = $item[$syncStatusName];
                    if (is_array($value) && !empty($value)) {
                        $value = reset($value) ? true : false;
                    } else {
                        $value = $value ? true : false;
                    }
                    if ($value) {
                        /** @var string $html */
                        $type = '-success success';
                        $title = __('In Sync');
                    }
                }

                $item[$syncStatusName . '_html'] =
                    '<div class="message message' . $type .
                    ' sync-status-salesforce" title="'.$title.'"></div>';
            }
        }

        return $dataSource;
    }
}
