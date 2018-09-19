<?php
namespace TNW\Salesforce\Block\Wizard\Step;

/**
 * Class TestConnection
 * @package TNW\Salesforce\Block\Wizard\Step
 */
class TestConnection extends \Magento\Backend\Block\Template
{
    /**
     * Get TestConnection URL
     *
     * @return string
     */
    public function getTestConnectionUrl()
    {
        return $this->getUrl('tnw_salesforce/wizard/testconnection');
    }
}
