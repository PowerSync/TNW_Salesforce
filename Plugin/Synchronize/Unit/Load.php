<?php
namespace TNW\Salesforce\Plugin\Synchronize\Unit;

use \TNW\Salesforce\Synchronize\Entity\DivideEntityByWebsiteOrg\Pool;

/**
 * Load
 */
class Load
{
    /** @var Pool  */
    protected $dividerPool;

    /** @var \TNW\Salesforce\Model\Config  */
    protected $config;

    /**
     * LoadByAbstract constructor.
     * @param Pool $pool
     * @param \TNW\Salesforce\Model\Config $config
     */
    public function __construct(
        Pool $pool,
        \TNW\Salesforce\Model\Config $config
    ) {
        $this->dividerPool = $pool;
        $this->config = $config;
    }

    /**
     * After Load Entity
     *
     * @param \TNW\Salesforce\Synchronize\Unit\Load $subject
     * @param \Magento\Framework\Model\AbstractModel $entity
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterLoadEntity(
        \TNW\Salesforce\Synchronize\Unit\Load $subject,
        $entity
    ) {
        $entityWebsiteIds = $this->dividerPool
            ->getDividerByGroupCode($subject->group()->code())
            ->getEntityWebsiteIds($entity);

        $entityOrgWebsites = array_intersect($entityWebsiteIds, $this->config->getCurrentOrgWebsites());
        return $entity->setData('config_website', current($entityOrgWebsites));
    }
}
