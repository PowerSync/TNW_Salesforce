<?php
namespace TNW\Salesforce\Plugin\Synchronize\Unit;

/**
 * Class CustomerGridAddAction
 * @package TNW\Salesforce\Plugin
 */
class LoadByAbstract extends LoadAbstract
{

    /**
     * @param \TNW\Salesforce\Synchronize\Unit\LoadByAbstract $subject
     * @param $entities \Magento\Framework\Model\AbstractModel[]
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterLoadByEntities(
        \TNW\Salesforce\Synchronize\Unit\LoadByAbstract $subject,
        $entities
    ) {

        foreach ($entities as &$entity) {
            $entity = $this->setEntityConfigWebsite($subject, $entity);
        }

        return $entities;
    }
}
