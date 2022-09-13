<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Synchronize\Entity;

use TNW\Salesforce\Api\CleanableInstanceInterface;
use TNW\Salesforce\Client\Salesforce;

/**
 * DivideEntityByWebsiteOrg
 */
abstract class DivideEntityByWebsiteOrg implements CleanableInstanceInterface
{
    /**
     * @var \TNW\Salesforce\Model\Config
     */
    protected $config;

    /** @var array */
    private $processed = [];

    /** @var array */
    private $entitiesByWebsites = [];

    /**
     * Entity constructor.
     *
     * @param \TNW\Salesforce\Model\Config $config
     */
    public function __construct(
        \TNW\Salesforce\Model\Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Entity Website Ids
     *
     * @param \Magento\Framework\Model\AbstractModel $entity
     *
     * @return int[]
     */
    abstract public function getEntityWebsiteIds($entity);

    /**
     * Load Entities
     *
     * @param int[] $ids
     *
     * @return \Magento\Framework\Model\AbstractModel[]
     */
    abstract public function loadEntities($ids);

    /**
     * Process
     *
     * @param array $entities
     *
     * @return array
     */
    public function process(array $entities)
    {
        $missedEntityIds = [];
        foreach ($entities as $entity) {
            if (!isset($this->processed[$entity])) {
                $missedEntityIds[] = $entity;
                $this->processed[$entity] = 1;
            }
        }

        foreach (array_chunk($missedEntityIds, Salesforce::SFORCE_UPSERT_CHUNK_SIZE) as $entitiesChunk) {
            foreach ($this->loadEntities($entitiesChunk) as $entity) {
                foreach ($this->getEntityWebsiteIds($entity) as $entityWebsiteId) {
                    $baseWebsiteId = $this->config->baseWebsiteIdLogin($entityWebsiteId);
                    $websiteId = in_array($entityWebsiteId, $this->config->getOrgWebsites($baseWebsiteId))
                        ? $entityWebsiteId
                        : $baseWebsiteId;

                    $this->entitiesByWebsites[$websiteId][$entity->getId()] = $entity->getId();
                }
            }
        }

        $result = [];
        $websiteIds = array_keys($this->entitiesByWebsites);
        foreach ($entities as $entity) {
            foreach ($websiteIds as $websiteId) {
                $entityId = $this->entitiesByWebsites[$websiteId][$entity] ?? null;
                if ($entityId !== null) {
                    $result[$websiteId][$entityId] = $entityId;
                }
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function clearLocalCache(): void
    {
        $this->processed = [];
        $this->entitiesByWebsites = [];
    }
}
