<?php
namespace TNW\Salesforce\Ui\Component\Listing\Columns;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use TNW\Salesforce\Model\ResourceModel\Objects;

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
        $syncStatusName = 'sforce_sync_status';

        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {

                $type = '-warning error';
                $title = __('Out of Sync');

                if (key_exists($syncStatusName, $item)) {
                    $value = $item[$syncStatusName];
                    if (is_array($value) && !empty($value)) {
                        $value = reset($value);
                    }
                    switch ($value) {
                        case Objects::SYNC_STATUS_IN_SYNC:
                            $type = '-success success';
                            $title = __('In Sync');
                            break;
                        case Objects::SYNC_STATUS_IN_SYNC_PENDING:
                        case Objects::SYNC_STATUS_OUT_OF_SYNC_PENDING:
                            $type = '-pending pending';
                            $title = __('Pending');
                            break;
                        default:
                            break;
                    }
                }

                $item[$syncStatusName . '_html'] =
                    '<div class="message message' . $type . ' sync-status-salesforce" title="' . $title . '"></div>';
            }
        }

        return $dataSource;
    }

    /**
     * @inheritdoc
     */
    protected function applySorting()
    {
        $dataProdider = $this->getContext()->getDataProvider();
        if (!$dataProdider instanceof \Magento\Ui\DataProvider\AbstractDataProvider) {
            parent::applySorting();
            return;
        }

        $sorting = $this->getContext()->getRequestParam('sorting');
        $isSortable = $this->getData('config/sortable');
        if ($isSortable !== false
            && !empty($sorting['field'])
            && !empty($sorting['direction'])
            && $sorting['field'] === $this->getName()
        ) {
            $dataProdider->getCollection()->getSelect()
                ->order(new \Zend_Db_Expr($this->getName() . ' ' . strtoupper($sorting['direction'])));
        }
    }
}
