<?php
namespace TNW\Salesforce\Model;

use TNW\Salesforce\Model\ResourceModel;

/**
 * Class Queue
 *
 * @method \TNW\Salesforce\Model\ResourceModel\Queue _getResource()
 */
class Queue extends \Magento\Framework\Model\AbstractModel
{
    const STATUS_NEW = 'new';
    const STATUS_ERROR = 'error';
    const STATUS_COMPLETE = 'complete';
    const STATUS_SKIPPED = 'skipped';
    const STATUS_WAITING_UPSERT = 'waiting_upsert';
    const STATUS_WAITING_LOOKUP = 'waiting_lookup';
    const STATUS_PROCESS_INPUT_UPSERT = 'process_upsert_input';
    const STATUS_PROCESS_INPUT_LOOKUP = 'process_lookup_input';
    const STATUS_PROCESS_OUTPUT_UPSERT = 'process_output_upsert';
    const STATUS_PROCESS_OUTPUT_LOOKUP = 'process_output_lookup';

    /**
     * @var Queue[]
     */
    private $dependence = [];

    /**
     * Construct
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\Queue::class);
    }

    /**
     * Code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->_getData('code');
    }

    /**
     * Entity Type
     *
     * @return string
     */
    public function getEntityType()
    {
        return $this->_getData('entity_type');
    }

    /**
     * Object Type
     *
     * @return string
     */
    public function getObjectType()
    {
        return $this->_getData('object_type');
    }

    /**
     * Entity Id
     *
     * @return int
     */
    public function getEntityId()
    {
        return $this->_getData('entity_id');
    }

    /**
     * Entity Load
     *
     * @return string
     */
    public function getEntityLoad()
    {
        return $this->_getData('entity_load');
    }

    /**
     * Entity Load Additional
     *
     * @return array
     */
    public function getEntityLoadAdditional()
    {
        return (array)$this->_getData('entity_load_additional');
    }

    /**
     * Sync Type
     *
     * @return mixed
     */
    public function getSyncType()
    {
        return $this->_getData('sync_type');
    }

    /**
     * Website Id
     *
     * @return string
     */
    public function getWebsiteId()
    {
        return $this->_getData('website_id');
    }

    /**
     * Status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->_getData('status');
    }

    /**
     * Sync Attempt
     *
     * @return int
     */
    public function getSyncAttempt()
    {
        return (int)$this->_getData('sync_attempt');
    }

    /**
     * Is Error
     *
     * @return bool
     */
    public function isError()
    {
        return strcasecmp($this->_getData('status'), self::STATUS_ERROR) === 0;
    }

    /**
     * Is Error
     *
     * @return bool
     */
    public function isSkipped()
    {
        return strcasecmp($this->_getData('status'), self::STATUS_SKIPPED) === 0;
    }

    /**
     * Is Error
     *
     * @return bool
     */
    public function isComplete()
    {
        return strcasecmp($this->_getData('status'), self::STATUS_COMPLETE) === 0;
    }

    /**
     * Is Upsert Waiting
     *
     * @return bool
     */
    public function isWaitingUpsert()
    {
        return strcasecmp($this->_getData('status'), self::STATUS_WAITING_UPSERT) === 0;
    }

    /**
     * Id Upsert Input
     *
     * @return bool
     */
    public function isProcessInputUpsert()
    {
        return strcasecmp($this->_getData('status'), self::STATUS_PROCESS_INPUT_UPSERT) === 0;
    }

    /**
     * Is Upsert Output
     *
     * @return bool
     */
    public function isProcessOutputUpsert()
    {
        return strcasecmp($this->_getData('status'), self::STATUS_PROCESS_OUTPUT_UPSERT) === 0;
    }

    /**
     * Set Dependence
     *
     * @param Queue[] $queues
     * @return Queue
     */
    public function setDependence(array $queues)
    {
        $this->_hasDataChanges = true;
        $this->dependence = $queues;
        return $this;
    }

    /**
     * Add Dependence
     *
     * @param Queue $queue
     * @return Queue
     */
    public function addDependence($queue)
    {
        $this->_hasDataChanges = true;
        $this->dependence[] = $queue;
        return $this;
    }

    /**
     * Get Dependence
     *
     * @return Queue[]
     */
    public function getDependence()
    {
        return $this->dependence;
    }

    /**
     * Dependence By Code
     *
     * @param string $code
     * @return Queue
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function dependenceByCode($code)
    {
        $dependent = clone $this;
        $dependent->dependence = [];
        $dependent->_data = [];

        $this->_getResource()->loadByChild($dependent, $code, $this->getId());
        return $dependent;
    }

    /**
     * Exists Child By Code
     *
     * @param string $code
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function existsChildByCode($code)
    {
        return (bool)$this->_getResource()->childIdByCode($this->getId(), $code);
    }
}
