<?php

namespace TNW\Salesforce\Block\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class UpsellItem extends Field
{
    const WIZARD_TEMPLATE = 'Magento_Paypal::system/config/bml_api_wizard.phtml';
    /**
     * Path to template file in theme.
     *
     * @var string
     */
    protected $_template = 'TNW_Salesforce::system/config/button.phtml';

    /**
     * Retrieve HTML markup for given form element
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $isCheckboxRequired = $this->_isInheritCheckboxRequired($element);

        // Disable element if value is inherited from other scope. Flag has to be set before the value is rendered.
        if ($element->getInherit() == 1 && $isCheckboxRequired) {
            $element->setDisabled(true);
        }

//        $html = '<td class="label"><label for="' .
//            $element->getHtmlId() . '"><span' .
//            $this->_renderScopeLabel($element) . '>' .
//            $element->getLabel() .
//            '</span></label></td>';
        $html = $this->_renderValue($element);

        if ($isCheckboxRequired) {
//            $html .= $this->_renderInheritCheckbox($element);
        }

//        $html .= $this->_renderHint($element);

        return $this->_decorateRowHtml($element, $html);
    }

    /**
     * Decorate field row html
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @param string $html
     * @return string
     */
    protected function _decorateRowHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element, $html)
    {
        return '<div id="row_' . $element->getHtmlId() . '" class="' . $element->getClass() . '">' . $html . '</div>';
    }

    /**
     * Render element value
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _renderValue(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        if ($element->getTooltip()) {
//            $html = '<td class="value with-tooltip">';
            $html = $this->_getElementHtml($element);
//            $html .= '<div class="tooltip"><span class="help"><span></span></span>';
//            $html .= '<div class="tooltip-content">' . $element->getTooltip() . '</div></div>';
        } else {
//            $html = '<td class="value">';
            $html = $this->_getElementHtml($element);
        }
//        if ($element->getComment()) {
//            $html .= '<p class="note"><span>' . $element->getComment() . '</span></p>';
//        }
//        $html .= '</td>';
        return $html;
    }


    /**
     * Get the button and scripts contents
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $originalData = $element->getOriginalData();
        $this->addData(
            [
                'button_label' => __($originalData['button_label']),
                'button_url' => $originalData['button_url'],
                'html_id' => $element->getHtmlId(),
            ]
        );
        return $this->_toHtml();
    }
}
