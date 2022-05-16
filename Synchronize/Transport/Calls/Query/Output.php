<?php
namespace TNW\Salesforce\Synchronize\Transport\Calls\Query;

use ReturnTypeWillChange;

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
    public function current(): array
    {
        return \current($this->results);
    }

    /**
     * Move forward to next element
     */
    public function next(): void
    {
        \next($this->results);
    }

    /**
     * Return the key of the current element
     * @return int
     */
    public function key(): int
    {
        return \key($this->results);
    }

    /**
     * Checks if current position is valid
     * @return bool
     */
    public function valid(): bool
    {
        return \key($this->results) !== null;
    }

    /**
     * Rewind the Iterator to the first element
     */
    public function rewind(): void
    {
        \reset($this->results);
    }

    /**
     * Whether a offset exists
     * @param int $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->results);
    }

    /**
     * Offset to retrieve
     * @param int $offset
     * @return mixed
     */
    #[ReturnTypeWillChange] public function offsetGet($offset)
    {
        return $this->results[$offset];
    }

    /**
     * Offset to set
     * @param int $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->results[] = $value;
    }

    /**
     * Offset to unset
     * @param int $offset
     */
    public function offsetUnset($offset): void
    {
        unset($this->results[$offset]);
    }

    /**
     * Count elements of an object
     * @return int
     */
    public function count(): int
    {
        return \count($this->results);
    }
}
