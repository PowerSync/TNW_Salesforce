<?php

namespace TNW\Salesforce\Block\Adminhtml\Customer\Edit\Renderer;


class SyncStatus extends \Magento\Framework\Data\Form\Element\Label
{
    /** @var \TNW\Salesforce\Model\Customer\Attribute\Source\SyncStatus */
    private $syncStatus;

    public function __construct(
        \Magento\Framework\Data\Form\Element\Factory $factoryElement,
        \Magento\Framework\Data\Form\Element\CollectionFactory $factoryCollection,
        \Magento\Framework\Escaper $escaper,
        \TNW\Salesforce\Model\Customer\Attribute\Source\SyncStatus $syncStatus,
        array $data
    ) {
        parent::__construct($factoryElement, $factoryCollection, $escaper,
            $data);
        $this->syncStatus = $syncStatus;
    }

    public function getElementHtml()
    {
        $value = $this->getValue() ? 1 : 0;
        $this->setValue($this->syncStatus->getOptionText($value));

        return parent::getElementHtml();
    }
}
