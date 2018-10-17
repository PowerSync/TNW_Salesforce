<?php

namespace TNW\Salesforce\Synchronize;

class Entity
{
    /**
     * @var \TNW\Salesforce\Synchronize\Group
     */
    protected $synchronizeGroup;

    /** @var Entity\DivideEntityByWebsiteOrg  */
    protected $divideEntityByWebsiteOrg;

    /** @var \TNW\Salesforce\Model\Config\WebsiteEmulator  */
    protected $websiteEmulator;

    /**
     * Entity constructor.
     * @param Group $synchronizeGroup
     * @param Entity\DivideEntityByWebsiteOrg $divideEntityByWebsiteOrg
     * @param \TNW\Salesforce\Model\Config\WebsiteEmulator $websiteEmulator
     */
    public function __construct(
        \TNW\Salesforce\Synchronize\Group $synchronizeGroup,
        \TNW\Salesforce\Synchronize\Entity\DivideEntityByWebsiteOrg $divideEntityByWebsiteOrg,
        \TNW\Salesforce\Model\Config\WebsiteEmulator $websiteEmulator
    )
    {
        $this->synchronizeGroup = $synchronizeGroup;
        $this->divideEntityByWebsiteOrg = $divideEntityByWebsiteOrg;
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
            $entitiesByWebsite = $this->divideEntityByWebsiteOrg->process($entities);
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