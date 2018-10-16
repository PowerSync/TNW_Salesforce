<?php

namespace TNW\Salesforce\Synchronize;

class Entity
{
    /**
     * @var \TNW\Salesforce\Synchronize\Group
     */
    protected $synchronizeGroup;

    /** @var DevideEntityByWebsiteOrg */
    protected $devideEntityByWebsiteOrg;

    /** @var \TNW\Salesforce\Model\Config\WebsiteEmulator  */
    protected $websiteEmulator;

    /**
     * Entity constructor.
     * @param \TNW\Salesforce\Synchronize\Entity\DevideEntityByWebsiteOrg $devideEntityByWebsiteOrg
     */
    public function __construct(
        \TNW\Salesforce\Synchronize\Group $synchronizeGroup,
        \TNW\Salesforce\Synchronize\Entity\DevideEntityByWebsiteOrg $devideEntityByWebsiteOrg,
        \TNW\Salesforce\Model\Config\WebsiteEmulator $websiteEmulator
    )
    {
        $this->synchronizeGroup = $synchronizeGroup;
        $this->devideEntityByWebsiteOrg = $devideEntityByWebsiteOrg;
        $this->websiteEmulator = $websiteEmulator;
    }

    /**
     * @return Group
     */
    public function group()
    {
        return $this->synchronizeGroup;
    }

    /**
     * @param array $entities
     */
    public function synchronize(array $entities)
    {
        $this->synchronizeGroup->messageDebug('Start entity "%s" synchronize', $this->synchronizeGroup->code());

        try {
            $entitiesByWebsite = $this->devideEntityByWebsiteOrg->process($entities);
            foreach ($entitiesByWebsite as $websiteId => $ents) {

                $synchronizeGroup = $this->synchronizeGroup;

                $this->websiteEmulator->wrapEmulationWebsite(function() use ($synchronizeGroup, $ents) {
                    $synchronizeGroup->synchronize($ents);
                }, $websiteId);

            }
        } catch (\Exception $e) {
            $this->synchronizeGroup->messageError($e);
        }

        $this->synchronizeGroup->messageDebug('Stop entity "%s" synchronize', $this->synchronizeGroup->code());
    }
}