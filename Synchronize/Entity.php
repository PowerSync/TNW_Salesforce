<?php
namespace TNW\Salesforce\Synchronize;

class Entity
{
    /**
     * @var \TNW\Salesforce\Synchronize\Group
     */
    protected $synchronizeGroup;

    /**
     * @var Entity\DivideEntityByWebsiteOrg\Pool
     */
    protected $dividerPool;

    /**
     * @var \TNW\Salesforce\Model\Config\WebsiteEmulator
     */
    protected $websiteEmulator;

    /** @var \TNW\Salesforce\Model\Config */
    protected $salesforceConfig;

    /**
     * Entity constructor.
     * @param Group $synchronizeGroup
     * @param Entity\DivideEntityByWebsiteOrg\Pool $dividerPool
     * @param \TNW\Salesforce\Model\Config\WebsiteEmulator $websiteEmulator
     */
    public function __construct(
        Group $synchronizeGroup,
        Entity\DivideEntityByWebsiteOrg\Pool $dividerPool,
        \TNW\Salesforce\Model\Config\WebsiteEmulator $websiteEmulator,
        \TNW\Salesforce\Model\Config $salesforceConfig
    ) {
        $this->synchronizeGroup = $synchronizeGroup;
        $this->dividerPool = $dividerPool;
        $this->websiteEmulator = $websiteEmulator;
        $this->salesforceConfig = $salesforceConfig;
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
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function synchronize(array $entities)
    {

        if (count($entities) > $this->salesforceConfig::REALTIME_MAX_SYNC) {
            $this->synchronizeGroup->messageNotice(
                'Too much (%s) entities for Realtime sync, first %s entities synced.',
                count($entities),
                $this->salesforceConfig::REALTIME_MAX_SYNC
            );
            $entities = array_slice($entities, 0, $this->salesforceConfig::REALTIME_MAX_SYNC);
        }

        $entitiesByWebsite = $this->dividerPool
            ->getDividerByGroupCode($this->synchronizeGroup->code())
            ->process($entities);

        foreach ($entitiesByWebsite as $websiteId => $entityIds) {
            $this->websiteEmulator->wrapEmulationWebsite(function($websiteId) use ($entityIds) {
                $this->synchronizeGroup->messageDebug(
                    'Start entity "%s" synchronize for website %s',
                    $this->synchronizeGroup->code(),
                    $websiteId
                );

                if (!$this->salesforceConfig->getSalesforceStatus()) {
                    $this->synchronizeGroup->messageNotice(
                        'Sync is disabled in the website #"%s" config',
                        $websiteId
                    );
                    return;
                }

                try {
                    $this->synchronizeGroup->synchronize($entityIds);
                } catch (\Exception $e) {
                    $this->synchronizeGroup->messageError($e);
                }

                $this->synchronizeGroup->messageDebug(
                    'Stop entity "%s" synchronize for website %s',
                    $this->synchronizeGroup->code(),
                    $websiteId
                );
            }, $websiteId);
        }
    }
}
