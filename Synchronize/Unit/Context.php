<?php
namespace TNW\Salesforce\Synchronize\Unit;

use TNW\Salesforce\Model\Entity\SalesforceIdStorage;
use TNW\Salesforce\Synchronize;

class Context extends Synchronize\Unit\UnitAbstract
{

    /**
     * @var IdentificationInterface
     */
    protected $identification;

    /**
     * @var SalesforceIdStorage
     */
    protected $entityObject;

    /**
     * @var SalesforceIdStorage
     */
    protected $salesforceIdStorage;

    /**
     * @var HashInterface
     */
    protected $hash;

    /**
     * @var string
     */
    protected $magentoType;

    /**
     * @var string
     */
    protected $objectType;

    /**
     * @var string
     */
    protected $salesforceType;

    /**
     * @var string
     */
    protected $fieldSalesforceId;

    /**
     * @param string $name
     * @param Synchronize\Units $units
     * @param Synchronize\Group $group
     * @param Synchronize\Unit\IdentificationInterface $identification
     * @param SalesforceIdStorage|null $entityObject
     * @param SalesforceIdStorage $salesforceIdStorage
     * @param HashInterface $hash
     * @param string $magentoType
     * @param string $objectType
     * @param string $salesforceType
     * @param string $fieldSalesforceId
     */
    public function __construct(
        string $name,
        Synchronize\Units $units,
        Synchronize\Group $group,
        IdentificationInterface $identification,
        SalesforceIdStorage $entityObject,
        SalesforceIdStorage $salesforceIdStorage,
        HashInterface $hash,
        string $magentoType,
        string $objectType,
        string $salesforceType,
        string $fieldSalesforceId
    ) {
        parent::__construct($name, $units, $group);
        $this->identification = $identification;
        $this->entityObject = $entityObject;
        $this->salesforceIdStorage = $salesforceIdStorage;
        $this->hash = $hash;
        $this->magentoType = $magentoType;
        $this->objectType = $objectType;
        $this->salesforceType = $salesforceType;
        $this->fieldSalesforceId = $fieldSalesforceId;
    }

    /**
     * @return IdentificationInterface
     */
    public function identification()
    {
        return $this->identification;
    }

    /**
     * @return SalesforceIdStorage
     */
    public function entityObject()
    {
        return $this->entityObject;
    }

    /**
     * @return SalesforceIdStorage
     */
    public function salesforceIdStorage()
    {
        return $this->salesforceIdStorage;
    }

    /**
     * @return HashInterface
     */
    public function hash()
    {
        return $this->hash;
    }

    /**
     * @return string
     */
    public function magentoType()
    {
        return $this->magentoType;
    }

    /**
     * @return string
     */
    public function objectType()
    {
        return $this->objectType;
    }

    /**
     * @return string
     */
    public function salesforceType()
    {
        return $this->salesforceType;
    }

    /**
     * @return string
     */
    public function fieldSalesforceId()
    {
        return $this->fieldSalesforceId;
    }

    public function process()
    {

    }
}
