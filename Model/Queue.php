<?php
namespace TNW\Salesforce\Model;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

/**
 * Class Queue
 *
 * @method ResourceModel\Queue _getResource()
 */
class Queue extends AbstractModel
{
    public const UNIQUE_HASH = 'unique_hash';

    const STATUS_NEW = 'new';
    const STATUS_ERROR = 'error';
    const STATUS_COMPLETE = 'complete';
    const STATUS_SKIPPED = 'skipped';
    const STATUS_WAITING_UPSERT = 'waiting_upsert';
    const STATUS_WAITING_LOOKUP = 'waiting_lookup';
    const STATUS_ERROR_INPUT_UPSERT = 'error_upsert_input';
    const STATUS_ERROR_INPUT_LOOKUP = 'error_lookup_input';
    const STATUS_PROCESS_INPUT_UPSERT = 'process_upsert_input';
    const STATUS_PROCESS_INPUT_LOOKUP = 'process_lookup_input';
    const STATUS_ERROR_OUTPUT_UPSERT = 'error_output_upsert';
    const STATUS_ERROR_OUTPUT_LOOKUP = 'error_output_lookup';
    const STATUS_PROCESS_OUTPUT_UPSERT = 'process_output_upsert';
    const STATUS_PROCESS_OUTPUT_LOOKUP = 'process_output_lookup';

    const STATUS_PROCESS = 'process';
    const STATUS_BLOCKED = 'blocked';
    const STATUS_PARTIAL = 'partial';

    const SUCCESS_STATUSES = [
        self::STATUS_COMPLETE,
        self::STATUS_SKIPPED,
    ];

    const PROCESS_STATUSES = [
        self::STATUS_PROCESS,
        self::STATUS_WAITING_UPSERT,
        self::STATUS_WAITING_LOOKUP,
        self::STATUS_PROCESS_INPUT_UPSERT,
        self::STATUS_PROCESS_INPUT_LOOKUP,
        self::STATUS_PROCESS_OUTPUT_UPSERT,
        self::STATUS_PROCESS_OUTPUT_LOOKUP,
    ];

    const ERROR_STATUSES = [
        self::STATUS_ERROR,
        self::STATUS_ERROR_INPUT_UPSERT,
        self::STATUS_ERROR_INPUT_LOOKUP,
        self::STATUS_ERROR_OUTPUT_UPSERT,
        self::STATUS_ERROR_OUTPUT_LOOKUP,
    ];

    /**
     * @var Queue[]
     */
    protected $dependence = [];

    protected $salesforceConfig = [];

    public function __construct(
        Context $context,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        Config $salesforceConfig,
        array $data = []
    ) {
        $this->salesforceConfig = $salesforceConfig;

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

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
     * Get Additional
     *
     * @return array
     */
    public function getAdditional()
    {
        return (array)$this->_getData('additional_data');
    }

    /**
     * Get Additional By Code
     *
     * @param string $code
     * @return mixed
     */
    public function getAdditionalByCode($code)
    {
        return $this->getDataByPath("additional_data/$code");
    }

    /**
     * Set Additional By Code
     *
     * @param string $code
     * @param mixed $value
     * @return Queue
     */
    public function setAdditionalByCode($code, $value)
    {
        $data = $this->_getData('additional_data');
        $data[$code] = $value;

        return $this->setData('additional_data', $data);
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

    public function isRealtime()
    {
        return $this->getSyncType() == Config::DIRECT_SYNC_TYPE_REALTIME;
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
     * Increment Sync Attempt
     *
     * @return Queue
     */
    public function incSyncAttempt()
    {
        // To restrict incrementing sync attempt upon processing from "In Progress: Salesforce Update" (waiting_upsert)
        if($this->isProcessOutputUpsert() === false || $this->getSyncAttempt() > $this->salesforceConfig->getMaxAdditionalAttemptsCount()) {
            $this->setData('sync_attempt', $this->getSyncAttempt() + 1);
        }
        return $this;
    }

    /**
     * Decrement Sync Attempt
     *
     * @return Queue
     */
    public function decrSyncAttempt()
    {
        $this->setData('sync_attempt', $this->getSyncAttempt() - 1);
        return $this;
    }

    /**
     * Is Error
     *
     * @return bool
     */
    public function isError()
    {
        return in_array($this->_getData('status'), self::ERROR_STATUSES, true);
    }

    /**
     * Is Success
     *
     * @return bool
     */
    public function isSuccess()
    {
        return in_array($this->_getData('status'), self::SUCCESS_STATUSES, true);
    }

    /**
     * Is Process
     *
     * @return bool
     */
    public function isProcess()
    {
        return in_array($this->_getData('status'), self::PROCESS_STATUSES, true);
    }

    /**
     * Is Skipped
     *
     * @return bool
     */
    public function isSkipped()
    {
        return strcasecmp($this->_getData('status'), self::STATUS_SKIPPED) === 0;
    }

    /**
     * Is Complete
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
     * @throws LocalizedException
     */
    public function dependenceByCode($code)
    {
        return $this->loadById($this->_getResource()->dependenceIdByCode($this->getId(), $code));
    }

    /**
     * Dependence By Entity Type
     *
     * @param string $entityType
     * @return Queue[]
     * @throws LocalizedException
     */
    public function dependenciesByEntityType($entityType)
    {
        return array_map(
            [$this, 'loadById'],
            $this->_getResource()->dependenceIdsByEntityType($this->getId(), $entityType)
        );
    }

    /**
     * Dependence By Entity Type
     *
     * @param string $entityType
     * @return Queue[]
     * @throws LocalizedException
     */
    public function childByEntityType($entityType)
    {
        return array_map(
            [$this, 'loadById'],
            $this->_getResource()->childIdsByEntityType($this->getId(), $entityType)
        );
    }

    /**
     * Load By Id
     *
     * @param int $queueId
     * @return Queue
     * @throws LocalizedException
     */
    public function loadById($queueId)
    {
        $queue = clone $this;
        $queue->dependence = [];
        $queue->_data = [];

        $this->_getResource()->load($queue, $queueId);
        return $queue;
    }

    /**
     * Exists Child By Code
     *
     * @param string $code
     * @return bool
     * @throws LocalizedException
     */
    public function existsChildByCode($code)
    {
        return (bool)$this->_getResource()->childIdByCode($this->getId(), $code);
    }
}
