<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\Model\Grid\Executor\ByCollection;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use TNW\Salesforce\Api\Model\Grid\GetColumnsDataItems\Executor\ByCollection\CreateCollection\ModifierInterface;

class CreateCollection
{
    /** @var string */
    private $collectionClassName;

    /** @var ModifierInterface[] */
    private $modifiers;

    /** @var AbstractResource */
    private $resource;

    /**
     * @param AbstractResource $resource
     * @param array              $modifiers
     * @param string             $collectionClassName
     */
    public function __construct(
        AbstractResource $resource,
        array              $modifiers,
        string             $collectionClassName
    ) {

        $this->resource = $resource;
        $this->modifiers = $modifiers;
        $this->collectionClassName = $collectionClassName;
    }

    /**
     * @param array|null $entityIds
     *
     * @return Collection
     * @throws LocalizedException
     */
    public function execute(array $entityIds = null): Collection
    {
        $collection = ObjectManager::getInstance()->create($this->collectionClassName);
        foreach ($this->modifiers as $modifier) {
            $modifier->execute($collection);
        }

        $entityIds !== null && $collection->addFieldToFilter(
            $this->resource->getIdFieldName(),
            ['in' => $entityIds]
        );

        return $collection;
    }
}
