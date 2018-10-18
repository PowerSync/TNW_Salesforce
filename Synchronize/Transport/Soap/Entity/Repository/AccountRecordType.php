<?php

namespace TNW\Salesforce\Synchronize\Transport\Soap\Entity\Repository;

use TNW\Salesforce\Synchronize;
use TNW\Salesforce\Synchronize\Transport;

/**
 * Class Base
 * @package TNW\Salesforce\Synchronize\Transport\Soap\Entity\Repository
 */
class AccountRecordType extends RecordType
{
    /** @var string  */
    const PROFESSIONAL_SALESFORCE_RECORD_TYPE_LABEL = 'Default';

    /** @var array */
    protected $defaultConditionsData = [
        'from' => 'RecordType',
        'columns' => [
            'Id',
            'Name'
        ],
        'where' => [
            'AND' => [
                'SobjectType' => ['=' => 'Account']
            ]
        ]
    ];

    /** @var \TNW\Salesforce\Model\Customer\Config */
    protected $config;

    /**
     * AccountRecordType constructor.
     * @param Transport\Calls\Query\InputFactory $inputFactory
     * @param Transport\Calls\Query\OutputFactory $outputFactory
     * @param Transport\Calls\QueryInterface $query
     * @param \TNW\SForceBusiness\Model\Customer\Config $config
     */
    public function __construct(
        Transport\Calls\Query\InputFactory $inputFactory,
        Transport\Calls\Query\OutputFactory $outputFactory,
        Transport\Calls\QueryInterface $query,
        \TNW\Salesforce\Model\Customer\Config $config
    )
    {
        $this->config = $config;
        parent::__construct($inputFactory, $outputFactory, $query);
    }


    /**
     * Get list of all B2C record types in SF
     * @return array|Transport\Calls\Query\Output
     */
    public function getPersonTypes()
    {

        $conditionsData = $this->defaultConditionsData;

        $results = [];
        try {
            if ($this->config->accountUsePersonAccounts()) {
                $conditionsData['where']['AND']['IsPersonType']['='] = true;

                $results = $this->search($conditionsData);
            }

        } catch (\Exception $e) {
            // Captures a usecase for Professional version of Salesforce
        }

        if (empty($results)) {
            $default = [
                'Id' => '',
                'Name' => __('-- Not Applicable --')
            ];

            $results[] = $default;
        }

        return $results;
    }

    /**
     * Get list of all B2B record types in SF
     * in case of PROFESSIONAL version of Salesforce catch error and return default empty value
     * @throws \Exception
     * @return array|Transport\Calls\Query\Output
     */
    public function getBusinessTypes()
    {
        $results = [];
        try {

            if ($this->config->accountUsePersonAccounts()) {
                $conditionsData['where']['AND']['IsPersonType']['='] = false;
            }

            $results = $this->search($conditionsData);

        } catch (\Exception $e) {
            // Captures a usecase for Professional version of Salesforce
        }

        if (empty($results)) {
            $default = [
                'Id' => '',
                'Name' => __(self::PROFESSIONAL_SALESFORCE_RECORD_TYPE_LABEL)
            ];

            $results[] = $default;
        }

        return $results;
    }

}