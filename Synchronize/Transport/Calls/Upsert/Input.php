<?php
namespace TNW\Salesforce\Synchronize\Transport\Calls\Upsert;

class Input extends \SplObjectStorage
{
    /**
     * @var array
     */
    private $info = [];

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
     * @return string
     */
    public function externalIdFieldName()
    {
        return $this->externalIdFieldName;
    }

    /**
     * @return string
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * @param object $object
     * @param array $data
     */
    public function offsetSet($object, $data = null)
    {
        $index = \spl_object_hash($object);
        parent::offsetSet($object, $index);
        $this->info[$index] = $data;
    }

    /**
     * @param object $object
     * @return array
     */
    public function &offsetGet($object)
    {
        if(!$this->contains($object)) {
            $this->offsetSet($object, []);
        }

        return $this->info[parent::offsetGet($object)];
    }

    /**
     * @return array
     */
    public function getInfo()
    {
        return $this->info[parent::getInfo()];
    }

    /**
     * @param array $data
     */
    public function setInfo($data)
    {
        $index = \spl_object_hash($this->current());
        parent::setInfo($index);
        $this->info[$index] = $data;
    }

    /**
     * @param object $object
     */
    public function offsetUnset($object)
    {
        unset($this->info[parent::offsetGet($object)]);
        parent::offsetUnset($object);
    }
}