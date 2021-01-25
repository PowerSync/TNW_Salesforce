<?php

namespace TNW\Salesforce\Block\Adminhtml\Customer\Edit\Renderer;

use TNW\Salesforce\ViewModel\SyncStatus as ViewSyncStatus;

class SyncStatus extends \Magento\Framework\Data\Form\Element\Label
{
    /**
     * @var ViewSyncStatus
     */
    protected $viewSyncStatus;

    public function __construct(
        \Magento\Framework\Data\Form\Element\Factory $factoryElement,
        \Magento\Framework\Data\Form\Element\CollectionFactory $factoryCollection,
        \Magento\Framework\Escaper $escaper,
        ViewSyncStatus $viewSyncStatus,
        array $data
    ) {
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
        $this->viewSyncStatus = $viewSyncStatus;
    }

    public function getElementHtml()
    {
        return '<div class="control-value">'
            . $this->viewSyncStatus->getStatusHtml((int) $this->getValue())
            . '</div>';
    }
}
