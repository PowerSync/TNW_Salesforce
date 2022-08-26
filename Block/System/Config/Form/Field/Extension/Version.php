<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Block\System\Config\Form\Field\Extension;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\DeploymentConfig\Reader as ConfigReader;
use Magento\Framework\Composer\ComposerInformation;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Module\ModuleList;
use Magento\Framework\Module\PackageInfo;
use Magento\Setup\Model\InstallerFactory;

class Version extends Field
{
    /** @var PackageInfo  */
    protected $packageInfo;

    /**
     * Version constructor.
     * @param Context $context
     * @param ComposerInformation $composer
     * @param PackageInfo $packageInfo
     * @param array $data
     */
    public function __construct(
        Context $context,
        ComposerInformation $composer,
        PackageInfo $packageInfo,
        array $data = []
    ) {
        $this->packageInfo = $packageInfo;

        parent::__construct($context, $data);
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setReadonly(1);

        $version = $this->packageInfo->getVersion($this->getModuleName());
        $element->setValue($version);

        return $element->getElementHtml();
    }
}
