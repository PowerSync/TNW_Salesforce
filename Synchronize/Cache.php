<?php
namespace TNW\Salesforce\Synchronize;

class Cache implements \ArrayAccess, \IteratorAggregate, \Countable
{
    /**
     * @var array
     */
    private $data;

    public function __construct(
        array &$data
    ) {
        $this->data = &$data;
    }

    /**
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
     * @param $path
     * @param array ...$objects
     * @return mixed|null
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
     * @param mixed $offset
     * @throws \InvalidArgumentException
     */
    public function offsetUnset($offset)
    {
        $offset = $this->prepareHash($offset);
        unset($this->data[$offset]);
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * @param $cache
     * @return bool
     */
    static public function isEmpty($cache)
    {
        if ($cache instanceof self) {
            return $cache->count() === 0;
        }

        return empty($cache);
    }
}