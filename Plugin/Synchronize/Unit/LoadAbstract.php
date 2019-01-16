<?php
namespace TNW\Salesforce\Plugin\Synchronize\Unit;

use \TNW\Salesforce\Synchronize\Entity\DivideEntityByWebsiteOrg\Pool;
/**
 * Class CustomerGridAddAction
 * @package TNW\Salesforce\Plugin
 */
class LoadAbstract
{

    /** @var Pool  */
    protected $dividerPool;

    /**
     * LoadByAbstract constructor.
     * @param Pool $pool
     */
    public function __construct(Pool $pool)
    {
        $this->dividerPool = $pool;

    }

    /**
     * @param $type
     * @return \TNW\Salesforce\Synchronize\Entity\DivideEntityByWebsiteOrg
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getDivider($type)
    {
        return $this->dividerPool
            ->getDividerByGroupCode($type);
    }

    /**
     * @param $subject \TNW\Salesforce\Synchronize\Unit\LoadAbstract|\TNW\Salesforce\Synchronize\Unit\LoadByAbstract
     * @param $entity
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setEntityConfigWebsite($subject, $entity)
    {
        $divider = $this->getDivider($subject->group()->code());

        $websiteIds = $divider->getEntityWebsiteIds($entity);

        $entity->setConfigWebsite(current($websiteIds));

        return $entity;
    }

    /**
     * @param \TNW\Salesforce\Synchronize\Unit\LoadAbstract $subject
     * @param $entity
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterLoadEntity(
        \TNW\Salesforce\Synchronize\Unit\LoadAbstract $subject,
        $entity
    ) {

        $entity = $this->setEntityConfigWebsite($subject, $entity);

        return $entity;
    }
}
