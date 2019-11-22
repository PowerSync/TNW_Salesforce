<?php

namespace TNW\Salesforce\Synchronize;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

/**
 * Bulk Client
 */
class Exception extends LocalizedException
{
    /** @var string */
    protected $queueStatus;

    /**
     * Exception constructor.
     * @param Phrase $phrase
     * @param \Exception|null $cause
     * @param int $code
     * @param string $queueStatus
     */
    public function __construct(Phrase $phrase, \Exception $cause = null, $code = 0, $queueStatus = 'errorStatus')
    {
        $this->queueStatus = $queueStatus;
        parent::__construct($phrase, $cause, $code);
    }

    /**
     * @return string
     */
    public function getQueueStatus()
    {
        return $this->queueStatus;
    }

    /**
     * @param string $queueStatus
     */
    public function setQueueStatus(string $queueStatus)
    {
        $this->queueStatus = $queueStatus;
    }
}
