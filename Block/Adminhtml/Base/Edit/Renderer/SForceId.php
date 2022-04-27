<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Block\Adminhtml\Base\Edit\Renderer;

use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Escaper;

abstract class SForceId extends \Magento\Framework\Data\Form\Element\Link
{
    /** @var \TNW\Salesforce\Client\Salesforce  */
    private $client;

    /** @var \TNW\Salesforce\Model\ResourceModel\Objects */
    protected $resourceObjects;

    /** @var \Magento\Framework\Registry  */
    protected $registry;

    /**
     * SForceId constructor.
     * @param Factory $factoryElement
     * @param CollectionFactory $factoryCollection
     * @param Escaper $escaper
     * @param array $data
     * @param \TNW\Salesforce\Client\Salesforce $client
     * @param \TNW\Salesforce\Model\ResourceModel\Objects $resourceObjects
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        array $data,
        \TNW\Salesforce\Client\Salesforce $client,
        \TNW\Salesforce\Model\ResourceModel\Objects $resourceObjects,
        \Magento\Framework\Registry $registry
    ) {
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
        $this->client = $client;
        $this->resourceObjects = $resourceObjects;
        $this->registry = $registry;
    }

    /**
     * @return integer
     */
    abstract public function getEntityId();

    /**
     * @return string
     */
    abstract public function getMagentoObjectType();

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getElementHtml()
    {
        return $this->generateLinkToSalesforce($this->getValue());
    }

    /**
     * @return mixed
     */
    public function getWebsite()
    {
        $websiteId = $this->getData('website_id');

        return $websiteId;
    }

    /**
     * @return null|string
     * @throws \Exception
     */
    public function getSalesforceObjectByAttribute()
    {
        $salesforceObject = null;

        return $salesforceObject;

    }

    /**
     * Generate link to specified object
     *
     * @param string $field
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function generateLinkToSalesforce($field)
    {
        $results = [];

        $websiteId = $this->getWebsite();

        $url = $this->client->getSalesForceUrl($websiteId);

        $magentoId = $this->getEntityId();
        $salesforceIds = $this->resourceObjects->loadObjectIds($magentoId, $this->getMagentoObjectType(), $websiteId);

        $salesforceObject = $this->getSalesforceObjectByAttribute();

        $field = !empty($salesforceIds[$salesforceObject])? $salesforceIds[$salesforceObject]: '';

        foreach (explode("\n", $field) as $value) {

            $addition = '';
            if (strpos($value, ':') !== false) {
                $tmp = explode(':', $value);
                $addition = $tmp[1] . ': ';
                $value = $tmp[2];
            }

            if (empty($value)) {
                continue;
            }

            if ($url) {
                $results[] = sprintf(
                    '%1$s<a target="_blank" style="font-family:monospace;" href="%2$s/%3$s" title="%4$s">%3$s</a>',
                    $addition,
                    $url,
                    $value,
                    __('Show on Salesforce')
                );
            } else {
                $results[] = $value;
            }
        }

        return sprintf('<div class="control-value">%s</div>', implode('<br>', $results));
    }
}
