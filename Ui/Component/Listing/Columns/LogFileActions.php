<?php
declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Ui\Component\Listing\Columns;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Log file actions.
 */
class LogFileActions extends Column
{
    /** @var UrlInterface */
    private $urlBuilder;

    /**
     * @param ContextInterface   $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface       $urlBuilder
     * @param array              $components
     * @param array              $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @inheritDoc
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $routePart = $this->getData('config', 'routePath') ?? '*/*';
        $routePart = trim((string)$routePart, '/');
        foreach ($dataSource['data']['items'] as &$item) {
            $name = $this->getData('name');
            if (isset($item['id'])) {
                $item[$name]['view'] = [
                    'href' => $this->urlBuilder->getUrl("$routePart/view", ['id' => $item['id']]),
                    'label' => __('View'),
                ];
                $item[$name]['download'] = [
                    'href' => $this->urlBuilder->getUrl("$routePart/download", ['id' => $item['id']]),
                    'label' => __('Download'),
                ];
            }
        }

        return $dataSource;
    }
}
