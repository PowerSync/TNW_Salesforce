<?php
namespace TNW\Salesforce\Model\Logger\Processor;

/**
 * UidProcessor
 */
class UidProcessor
{
    private $uid;

    /**
     * UidProcessor constructor.
     * @param int $length
     */
    public function __construct($length = 7)
    {
        if (!is_int($length) || $length > 32 || $length < 1) {
            throw new \InvalidArgumentException('The uid length must be an integer between 1 and 32');
        }

        $this->uid = substr(hash('md5', uniqid('', true)), 0, $length);
    }

    /**
     * Invoke
     *
     * @param array $record
     * @return array
     */
    public function __invoke(array $record)
    {
        $record['extra']['uid'] = $this->uid;

        return $record;
    }

    /**
     * Uid
     *
     * @return bool|string
     */
    public function uid()
    {
        return $this->uid;
    }
}
