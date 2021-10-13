<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Unit;

use TNW\Salesforce\Model\Entity\SalesforceIdStorage;
use TNW\Salesforce\Synchronize;

class Context extends Synchronize\Unit\UnitAbstract
{

    /**
     * @var Synchronize\Unit\IdentificationInterface
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
     * @var string|null
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
     * @param SalesforceIdStorage $salesforceIdStorage
     * @param string $magentoType
     * @param string|null $objectType
     * @param string $salesforceType
     * @param string $fieldSalesforceId
     */
    public function __construct(
        string $name,
        Synchronize\Units $units,
        Synchronize\Group $group,
        Synchronize\Unit\IdentificationInterface $identification,
        SalesforceIdStorage $salesforceIdStorage,
        string $magentoType,
        ?string $objectType,
        string $salesforceType,
        string $fieldSalesforceId
    ) {
        parent::__construct($name, $units, $group);
        $this->identification = $identification;
        $this->salesforceIdStorage = $salesforceIdStorage;
        $this->magentoType = $magentoType;
        $this->objectType = $objectType;
        $this->salesforceType = $salesforceType;
        $this->fieldSalesforceId = $fieldSalesforceId;
    }

    /**
     * @return IdentificationInterface
     */
    public function getIdentification(): IdentificationInterface
    {
        return $this->identification;
    }

    /**
     * @return SalesforceIdStorage
     */
    public function getSalesforceIdStorage(): SalesforceIdStorage
    {
        return $this->salesforceIdStorage;
    }

    /**
     * @return HashInterface
     */
    public function getHash(): HashInterface
    {
        return $this->hash;
    }

    /**
     * @return string
     */
    public function getMagentoType(): string
    {
        return $this->magentoType;
    }

    /**
     * @return string|null
     */
    public function getObjectType(): ?string
    {
        return $this->objectType;
    }

    /**
     * @return string
     */
    public function getSalesforceType(): string
    {
        return $this->salesforceType;
    }

    /**
     * @return string
     */
    public function getFieldSalesforceId(): string
    {
        return $this->fieldSalesforceId;
    }

    public function process()
    {

    }
}
