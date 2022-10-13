<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Synchronize\Unit\Customer\Mapping\Loader;

use Magento\Framework\Model\AbstractModel;
use Magento\Store\Model\WebsiteFactory;
use TNW\Salesforce\Model\Entity\SalesforceIdStorage;
use TNW\Salesforce\Service\Synchronize\Unit\Load\SubEntities\Load;
use TNW\Salesforce\Service\Synchronize\Unit\Load\SubEntities\Load\LoaderInterface;
use TNW\Salesforce\Synchronize\Unit\EntityLoaderAbstract;
use TNW\Salesforce\Synchronize\Unit\Load\EntityLoader\EntityPreLoaderInterface;

/**
 * Mapping Loader Website
 */
class Website extends EntityLoaderAbstract implements EntityPreLoaderInterface
{
    /** @var WebsiteFactory */
    private $factory;

    /** @var Load */
    private $loadSubEntities;

    /** @var LoaderInterface */
    private $loader;

    /** @var array */
    private $afterLoadExecutors;

    /**
     * Website constructor.
     *
     * @param WebsiteFactory           $factory
     * @param Load                     $loadSubEntities
     * @param LoaderInterface          $loader
     * @param array                    $afterLoadExecutors
     * @param SalesforceIdStorage|null $salesforceIdStorage
     */
    public function __construct(
        WebsiteFactory      $factory,
        Load                $loadSubEntities,
        LoaderInterface     $loader,
        array               $afterLoadExecutors = [],
        SalesforceIdStorage $salesforceIdStorage = null
    ) {
        parent::__construct($salesforceIdStorage);
        $this->factory = $factory;
        $this->loadSubEntities = $loadSubEntities;
        $this->loader = $loader;
        $this->afterLoadExecutors = $afterLoadExecutors;
    }

    /**
     * @inheritDoc
     */
    public function load($entity)
    {
        $entityId = spl_object_id($entity);

        return $this->loadSubEntities->execute($this, [$entity])[$entityId] ?? $this->createEmptyEntity();
    }

    /**
     * @inheritDoc
     */
    public function createEmptyEntity(): ?AbstractModel
    {
        return $this->factory->create();
    }

    /**
     * @inheritDoc
     */
    public function getLoader(): LoaderInterface
    {
        return $this->loader;
    }

    /**
     * @inheritDoc
     */
    public function getAfterPreloadExecutors(): array
    {
        return $this->afterLoadExecutors;
    }
}
