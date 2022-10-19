<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Synchronize\Unit\Website\Loader;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory;
use Magento\Store\Model\WebsiteFactory;
use TNW\Salesforce\Service\Synchronize\Unit\Load\PreLoadEntities;
use TNW\Salesforce\Synchronize\Unit\Load\PreLoaderInterface;
use TNW\Salesforce\Synchronize\Unit\LoadLoaderInterface;

/**
 * Load By Website
 */
class ByWebsite implements LoadLoaderInterface, PreLoaderInterface
{
    const LOAD_BY = 'website';

    /** @var PreLoadEntities */
    private $preLoadEntities;

    /** @var CollectionFactory */
    private $collectionFactory;

    /** @var WebsiteFactory */
    private $factory;

    /** @var array */
    private $afterLoadExecutors;

    /**
     * ByWebsite constructor.
     *
     * @param WebsiteFactory    $factory
     * @param PreLoadEntities   $preLoadEntities
     * @param CollectionFactory $collectionFactory
     * @param array             $afterLoadExecutors
     */
    public function __construct(
        WebsiteFactory    $factory,
        PreLoadEntities   $preLoadEntities,
        CollectionFactory $collectionFactory,
        array $afterLoadExecutors = []
    ) {
        $this->factory = $factory;
        $this->preLoadEntities = $preLoadEntities;
        $this->collectionFactory = $collectionFactory;
        $this->afterLoadExecutors = $afterLoadExecutors;
    }

    /**
     * @inheritDoc
     */
    public function loadBy()
    {
        return self::LOAD_BY;
    }

    /**
     * @inheritDoc
     */
    public function load($entityId, array $additional)
    {
        return $this->preLoadEntities->execute($this, [$entityId], [$entityId => $additional])[$entityId] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function createCollectionInstance(): ?AbstractDb
    {
        return $this->collectionFactory->create();
    }

    /**
     * @inheritDoc
     */
    public function createEmptyEntity(): AbstractModel
    {
        return $this->factory->create();
    }

    /**
     * @inheritDoc
     */
    public function getAfterPreLoadExecutors(): array
    {
        return $this->afterLoadExecutors;
    }
}
