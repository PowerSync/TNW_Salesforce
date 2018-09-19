<?php

namespace TNW\Salesforce\Block\System\Config\Form\Field\Extension;

class Version extends \Magento\Config\Block\System\Config\Form\Field
{
    protected $moduleList;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Module\ModuleList $moduleList,
        array $data = [])
    {
        $this->moduleList = $moduleList;
        parent::__construct($context, $data);
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement  $element)
    {
        $element->setReadonly(1);
        $module = $this->moduleList->getOne('TNW_Salesforce');
        if ($module && isset($module['setup_version'])) {
            $element->setValue($module['setup_version']);
        }

        return $element->getElementHtml();
    }

}
