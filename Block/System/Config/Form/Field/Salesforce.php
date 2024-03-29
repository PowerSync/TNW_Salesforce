<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Block\System\Config\Form\Field;

use \TNW\Salesforce\Model\Config;

class Salesforce extends \Magento\Config\Block\System\Config\Form\Field
{

    /** @var Config  */
    protected $salesforceConfig;

    /**
     * Salesforce constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     * @param Config $salesforceConfig
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        Config $salesforceConfig,
        array $data = []
    )
    {
        $this->salesforceConfig = $salesforceConfig;

        parent::__construct($context, $data);
    }


    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        if (!$this->salesforceConfig->isDefaultOrg()) {
            $element
                ->setCanUseDefaultValue(false)
                ->setCanUseWebsiteValue(false)
                ->setCanRestoreToDefault(false)
                ->setInherit(false);
        }

        return parent::render($element);
    }

}
