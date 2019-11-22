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
}