<?php
namespace TNW\Salesforce\Synchronize\Entity;

abstract class DivideEntityByWebsiteOrg
{

    /** @var \TNW\Salesforce\Model\Config  */
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
     * @param $entity
     * @return mixed
     */
    abstract public function getEntityWebsiteIds($entity);

    /**
     * @param $ids
     * @return mixed
     */
    abstract public function loadEntities($ids);

    /**
     * @param array $entities
     * @return array
     */
    public function process(array $entities)
    {
        $groupedWebsites = $this->config->getWebsitesGrouppedByOrg();

        $entities = $this->loadEntities($entities);

        $entitiesByWebsites = [];
        foreach ($entities as $entity) {
            foreach ($this->getEntityWebsiteIds($entity) as $entityWebsiteId) {
                $generalWebsiteId = $groupedWebsites[$entityWebsiteId];
                $entitiesByWebsites[$generalWebsiteId][$entity->getId()] = $entity->getId();
            }
        }

        return $entitiesByWebsites;
    }
}