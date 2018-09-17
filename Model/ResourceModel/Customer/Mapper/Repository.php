<?php

namespace  TNW\Salesforce\Model\ResourceModel\Customer\Mapper;


class Repository
{
    protected $cachedCollection;
    protected $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @return Collection
     */
    public function getResultCollection($type = \TNW\Salesforce\Model\Customer\Mapper::OBJECT_TYPE_CONTACT)
    {
        if(isset($this->cachedCollection[$type])){
            return $this->cachedCollection[$type];
        }

        //TODO: validate is it is correct way to do
        $collection = $this->objectManager->create('TNW\Salesforce\Model\ResourceModel\Customer\Mapper\Collection');
        $collection->addFieldToFilter('object_type', ['eq' => $type]);
        $this->cachedCollection[$type] = $collection;

        return $collection;
    }

}