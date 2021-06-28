<?php

namespace TNW\Salesforce\Synchronize\Queue;

use Magento\Framework\App\State;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use TNW\Salesforce\Model\Customer\Config;
use TNW\Salesforce\Model\ResourceModel\PreQueue;

class DependenciesQueue
{
    /**
     * @var array
     */
    public $queueAddPool;
    /**
     * @var PreQueue
     */
    private $resourcePreQueue;
    /**
     * @var State
     */
    private $state;
    /**
     * @var TimezoneInterface
     */
    private $timezone;
    /**
     * @var Config
     */
    private $config;

    /**
     * @var string[][]
     */
    protected $childrenList = [];

    /**
     * PreQueueCommand constructor.
     * @param array $queueAddPool
     * @param PreQueue $resourcePreQueue
     * @param State $state
     * @param TimezoneInterface $timezone
     * @param null $name
     */
    public function __construct(
        array $queueAddPool,
        PreQueue $resourcePreQueue,
        State $state,
        TimezoneInterface $timezone,
        Config $config,
        $name = null
    ) {
        $this->queueAddPool = $queueAddPool;
        $this->resourcePreQueue = $resourcePreQueue;
        $this->state = $state;
        $this->timezone = $timezone;
        $this->config = $config;
    }

    /**
     * Retrieve children codes grouped by resolve codes
     *
     * @return string[][]
     */
    public function getChildrenList()
    {
        if (!$this->childrenList) {
            $childrenList = [];
            if (isset($this->queueAddPool)) {
                foreach ($this->queueAddPool as $entity) {
                    if (isset($entity->resolves)) {
                        foreach ($entity->resolves as $resolve) {
                            foreach ($resolve->children() as $child) {
                                $this->childrenList[$resolve->code()][] = $child->code();
                            }
                        }
                    }
                }
            }
        }

        return $this->childrenList;
    }

    /**
     * Retrieve resolves code array by entity type
     *
     * @param string $entityType
     * @return string[]
     */
    public function getResolvesCodes(string $entityType)
    {
        $resolves = [];
        if (
            isset($this->queueAddPool)
            && isset($this->queueAddPool[$entityType])
            && isset($this->queueAddPool[$entityType]->resolves)
        ) {
            foreach ($this->queueAddPool[$entityType]->resolves as $item) {
                $resolves[] = $item->code();
            }
        }

        return $resolves;
    }

    /**
     * @param $descendantStr
     * @return array
     */
    public function parseDependencyString($descendantStr)
    {
        $descendantsTmp = explode('&', $descendantStr);
        $descendants = [];
        foreach ($descendantsTmp as $descendantTmp) {

            list($key, $value) = explode('=', $descendantTmp);
            $descendants[$key] = $value;
        }

        return $descendants;
    }


    /**
     * @param $descendantStr
     * @return array
     */
    public function parseDependencyStringWithReplace($descendantStr)
    {
        $descendants = $this->parseDependencyString($descendantStr);
        $descendants = str_replace('_', '.', array_keys($descendants));

        return $descendants;
    }
}
