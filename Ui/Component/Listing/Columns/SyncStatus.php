<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Ui\Component\Listing\Columns;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use TNW\Salesforce\Model\Objects\Status\Options;
use TNW\Salesforce\ViewModel\SyncStatus as ViewSyncStatus;

/**
 * Class SyncStatus
 * @package TNW\Salesforce\Ui\Component\Listing\Columns
 */
class SyncStatus extends Column
{
    /** @var  StoreManagerInterface */
    protected $storeManager;

    /**
     * @var ViewSyncStatus
     */
    protected $viewSyncStatus;

    /**
     * SyncStatus constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param StoreManagerInterface $storeManagerInterface
     * @param ViewSyncStatus $viewSyncStatus
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        StoreManagerInterface $storeManagerInterface,
        ViewSyncStatus $viewSyncStatus,
        array $components,
        array $data
    ) {
        $this->storeManager = $storeManagerInterface;
        $this->viewSyncStatus = $viewSyncStatus;
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
                $value = Options::STATUS_OUT_OF_SYNC;

                if (key_exists($syncStatusName, $item)) {
                    $value = $item[$syncStatusName];
                    if (is_array($value) && !empty($value)) {
                        $value = reset($value);
                    }
                }

                $item[$syncStatusName . '_html'] = $this->viewSyncStatus->getStatusHtml((int) $value);
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
