<?php
namespace TNW\Salesforce\Synchronize\Transport\Calls\Query;

class Output implements \Countable, \Iterator, \ArrayAccess
{
    /**
     * @var array
     */
    protected $results = [];

    /**
     * Return the current element
     * @return mixed
     */
    public function current()
    {
        return \current($this->results);
    }

    /**
     * Move forward to next element
     */
    public function next()
    {
        \next($this->results);
    }

    /**
     * Return the key of the current element
     * @return int
     */
    public function key()
    {
        return \key($this->results);
    }

    /**
     * Checks if current position is valid
     * @return bool
     */
    public function valid()
    {
        return \key($this->results) !== null;
    }

    /**
     * Rewind the Iterator to the first element
     */
    public function rewind()
    {
        \reset($this->results);
    }

    /**
     * Whether a offset exists
     * @param int $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->results);
    }

    /**
     * Offset to retrieve
     * @param int $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->results[$offset];
    }

    /**
     * Offset to set
     * @param int $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->results[] = $value;
    }

    /**
     * Offset to unset
     * @param int $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->results[$offset]);
    }

    /**
     * Count elements of an object
     * @return int
     */
    public function count()
    {
        return \count($this->results);
    }
}