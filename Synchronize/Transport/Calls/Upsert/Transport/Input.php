<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Synchronize\Transport\Calls\Upsert\Transport;

/**
 * Upsert Transport Input
 */
class Input extends \SplObjectStorage
{
    /**
     * @var array
     */
    private $info = [];

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var string
     */
    protected $externalIdFieldName = '';

    /**
     * @var string
     */
    protected $type = '';

    /**
     * Data constructor.
     *
     * @param string $type
     * @param string $externalIdFieldName
     */
    public function __construct($type, $externalIdFieldName = 'Id')
    {
        $this->type = $type;
        $this->externalIdFieldName = $externalIdFieldName;
    }

    /**
     * External Id Field Name
     *
     * @return string
     */
    public function externalIdFieldName()
    {
        return $this->externalIdFieldName;
    }

    /**
     * Type
     *
     * @return string
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * Offset Set
     *
     * @param object $object
     * @param array $data
     */
    public function offsetSet($object, $data = null): void
    {
        $index = \spl_object_hash($object);
        parent::offsetSet($object, $index);
        $this->info[$index] = $data;
    }

    /**
     * Offset Get
     *
     * @param object $object
     * @return array
     */
    public function &offsetGet($object): mixed
    {
        if (!$this->contains($object)) {
            $this->offsetSet($object, []);
        }

        return $this->info[parent::offsetGet($object)];
    }

    /**
     * Get Info
     *
     * @return array
     */
    public function getInfo(): array
    {
        return $this->info[parent::getInfo()];
    }

    /**
     * Set Info
     *
     * @param array $data
     */
    public function setInfo($data): void
    {
        $index = \spl_object_hash($this->current());
        parent::setInfo($index);
        $this->info[$index] = $data;
    }

    /**
     * Offset Unset
     *
     * @param object $object
     */
    public function offsetUnset($object): void
    {
        unset($this->info[parent::offsetGet($object)]);
        parent::offsetUnset($object);
    }

    /**
     * Get headers for transport.
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Set headers.
     *
     * @param array $headers
     */
    public function setHeaders(array $headers): void
    {
        foreach ($headers as $name => $value) {
            $this->headers[$name] = $value;
        }
    }
}
