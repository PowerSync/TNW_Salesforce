<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Entity;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use TNW\Salesforce\Client\Salesforce;
use TNW\Salesforce\Model\Config;

/**
 * DivideEntityByWebsiteOrg
 */
abstract class DivideEntityByWebsiteOrg
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * Entity constructor.
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Entity Website Ids
     *
     * @param AbstractModel $entity
     * @return array
     */
    abstract public function getEntityWebsiteIds($entity): array;

    /**
     * Load Entities
     *
     * @param array $ids
     * @return array|AbstractDb
     */
    abstract public function loadEntities($ids);

    /**
     * Process
     *
     * @param array $entities
     * @return array
     */
    public function process(array $entities): array
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
