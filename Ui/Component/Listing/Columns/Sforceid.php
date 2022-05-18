<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Ui\Component\Listing\Columns;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Sforceid extends Column
{
    /**
     * @var \TNW\Salesforce\Client\Salesforce
     */
    protected $client;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param \TNW\Salesforce\Client\Salesforce $client
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \TNW\Salesforce\Client\Salesforce $client,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);

        $this->client = $client;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function prepareDataSource(array $dataSource)
    {
        if (empty($dataSource['data']['items'])) {
            return $dataSource;
        }

        $fieldName = $this->getData('name');
        foreach ($dataSource['data']['items'] as &$item) {
            if (empty($item[$fieldName])) {
                continue;
            }

            $item["{$fieldName}_html"] = $this->generateLinkToSalesforce(
                $item[$fieldName],
                $this->websiteIdByItem($item)
            );
        }

        return $dataSource;
    }

    /**
     * @param array $item
     *
     * @return int
     */
    public function websiteIdByItem(array $item)
    {
        return isset($item['sf_website_id']) ? (int)$item['sf_website_id'] : 0;
    }

    /**
     * Generate link to specified object
     *
     * @param string $field
     * @param int $websiteId
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function generateLinkToSalesforce($field, $websiteId)
    {
        $result = [];

        $url = $this->client->getSalesForceUrl($websiteId);
        foreach (explode("\n", $field) as $value) {
            $currency = '';

            if (strpos($value, ':') !== false) {
                if (substr_count($value,':') === 2) {
                    [$storeId, $currency, $value] = explode(':', $value);
                    $currency = "$storeId:$currency";
                } else {
                    [$currency, $value] = explode(':', $value);
                }
                $currency .= ': ';
            }

            if (empty($value)) {
                continue;
            }

            if ($url) {
                $result[] = sprintf(
                    '%1$s<a target="_blank" style="font-family:monospace;" href="%2$s/%3$s" title="%4$s">%3$s</a>',
                    $currency,
                    $url,
                    $value,
                    __('Show on Salesforce')
                );
            } else {
                $result[] = sprintf('%s', $value);
            }
        }


        return implode('<br>', $result);
    }

    /**
     * @inheritdoc
     */
    protected function applySorting()
    {
        $dataProdider = $this->getContext()->getDataProvider();
        if(!$dataProdider instanceof \Magento\Ui\DataProvider\AbstractDataProvider) {
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
