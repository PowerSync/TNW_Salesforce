<?php
namespace TNW\Salesforce\Synchronize;

/**
 * Cache
 */
class Cache implements \ArrayAccess, \IteratorAggregate, \Countable
{
    /**
     * @var array
     */
    private $data;

    /**
     * Cache constructor.
     * @param array $data
     */
    public function __construct(
        array &$data
    ) {
        $this->data = &$data;
    }

    /**
     * Prepare Hash
     *
     * @param mixed $offset
     * @return mixed
     * @throws \InvalidArgumentException
     */
    protected function prepareHash($offset)
    {
        if (is_object($offset)) {
            return spl_object_hash($offset);
        }

        if (is_scalar($offset) && !is_bool($offset)) {
            return $offset;
        }

        throw new \InvalidArgumentException('Invalid entity key');
    }

    /**
     * Get
     *
     * @param string|null $path
     * @param array $objects
     * @return array|mixed|null
     */
    public function get($path = null, ...$objects)
    {
        if (null === $path) {
            return $this->data;
        }

        $objects = array_map([$this, 'prepareHash'], $objects);

        $record = $this->data;
        foreach (explode('/', sprintf($path, ...$objects)) as $field) {
            if (!isset($record[$field])) {
                return null;
            }

            $record = $record[$field];
        }

        return $record;
    }

    /**
     * Whether a offset exists
     *
     * @param mixed $offset
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function offsetExists($offset)
    {
        $offset = $this->prepareHash($offset);
        return isset($this->data[$offset]);
    }

    /**
     * Offset to retrieve
     *
     * @param mixed $offset
     * @return Cache
     * @throws \InvalidArgumentException
     */
    public function offsetGet($offset)
    {
        $offset = $this->prepareHash($offset);
        if (!isset($this->data[$offset])) {
            $this->data[$offset] = [];
        }

        return is_array($this->data[$offset])
            ? new self($this->data[$offset])
            : $this->data[$offset];
    }

    /**
     * Offset to set
     *
     * @param mixed $offset
     * @param mixed $value
     * @throws \InvalidArgumentException
     */
    public function offsetSet($offset, $value)
    {
        if (null === $offset) {
            $offset = count($this->data);
        }

        $offset = $this->prepareHash($offset);
        $this->data[$offset] = $value;
    }

    /**
     * Offset to unset
     *
     * @param mixed $offset
     * @throws \InvalidArgumentException
     */
    public function offsetUnset($offset)
    {
        $offset = $this->prepareHash($offset);
        unset($this->data[$offset]);
    }

    /**
     * Retrieve an external iterator
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    /**
     * Count elements of an object
     *
     * @return int
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * Is Empty
     *
     * @param Cache|mixed $cache
     * @return bool
     */
    public static function isEmpty($cache)
    {
        if ($cache instanceof self) {
            return $cache->count() === 0;
        }

        return empty($cache);
    }
}
