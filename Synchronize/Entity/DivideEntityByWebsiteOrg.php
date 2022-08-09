<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Synchronize\Entity;

use TNW\Salesforce\Client\Salesforce;

/**
 * DivideEntityByWebsiteOrg
 */
abstract class DivideEntityByWebsiteOrg
{
    /**
     * @var \TNW\Salesforce\Model\Config
     */
    protected $config;

    /**
     * Entity constructor.
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
     * @return int[]
     */
    abstract public function getEntityWebsiteIds($entity);

    /**
     * Load Entities
     *
     * @param int[] $ids
     * @return \Magento\Framework\Model\AbstractModel[]
     */
    abstract public function loadEntities($ids);

    /**
     * Process
     *
     * @param array $entities
     * @return array
     */
    public function process(array $entities)
    {
        $entitiesByWebsites = [];
        foreach (array_chunk($entities, Salesforce::SFORCE_UPSERT_CHUNK_SIZE) as $entitiesChunk) {
            foreach ($this->loadEntities($entitiesChunk) as $entity) {
                foreach ($this->getEntityWebsiteIds($entity) as $entityWebsiteId) {
                    $baseWebsiteId = $this->config->baseWebsiteIdLogin($entityWebsiteId);
                    $websiteId = in_array($entityWebsiteId, $this->config->getOrgWebsites($baseWebsiteId))
                        ? $entityWebsiteId
                        : $baseWebsiteId;

                    $entitiesByWebsites[$websiteId][$entity->getId()] = $entity->getId();
                }
            }
        }

        return $entitiesByWebsites;
    }
}
