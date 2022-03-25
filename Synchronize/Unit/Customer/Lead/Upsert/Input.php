<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Unit\Customer\Lead\Upsert;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use TNW\SForceEnterprise\Model\Synchronization\Config as SyncConfig;
use TNW\Salesforce\Synchronize\Group;
use TNW\Salesforce\Synchronize\Transport\Calls\Upsert\InputInterface;
use TNW\Salesforce\Synchronize\Transport\Calls\Upsert\Transport\Input as TransportInput;
use TNW\Salesforce\Synchronize\Transport\Calls\Upsert\Transport\InputFactory;
use TNW\Salesforce\Synchronize\Transport\Soap\ClientFactory;
use TNW\Salesforce\Synchronize\Unit\IdentificationInterface;
use TNW\Salesforce\Synchronize\Unit\Upsert\Input as BaseInput;
use TNW\Salesforce\Synchronize\Units;
use TNW\SForceEnterprise\SForceBusiness\Model\Customer\Config;

/**
 * Upsert input for customer lead entity.
 */
class Input extends BaseInput
{
    /** @var Config */
    private $leadConfig;

    /** @var SyncConfig */
    private $syncConfig;

    /**
     * @param                         $name
     * @param                         $load
     * @param                         $mapping
     * @param                         $salesforceType
     * @param Units                   $units
     * @param Group                   $group
     * @param IdentificationInterface $identification
     * @param InputFactory            $inputFactory
     * @param InputInterface          $process
     * @param ClientFactory           $factory
     * @param TimezoneInterface       $localeDate
     * @param Config                  $leadConfig
     * @param SyncConfig              $syncConfig
     */
    public function __construct(
        $name,
        $load,
        $mapping,
        $salesforceType,
        Units $units,
        Group $group,
        IdentificationInterface $identification,
        InputFactory $inputFactory,
        InputInterface $process,
        ClientFactory $factory,
        TimezoneInterface $localeDate,
        Config $leadConfig,
        SyncConfig $syncConfig
    ) {
        parent::__construct(
            $name,
            $load,
            $mapping,
            $salesforceType,
            $units,
            $group,
            $identification,
            $inputFactory,
            $process,
            $factory,
            $localeDate
        );
        $this->leadConfig = $leadConfig;
        $this->syncConfig = $syncConfig;
    }

    /**
     * @inheritDoc
     */
    public function processInput(TransportInput $input)
    {
        parent::processInput($input);
        if (!$input->count()) {
            return;
        }

        $assignedRule = $this->leadConfig->leadAssignmentRule();
        if ($assignedRule) {
            $isBulk = $this->syncConfig->getSyncType() !== SyncConfig::CRON_SYNC_TYPE_REALTIME;
            $protocol = $isBulk ? 'api' : 'soap';
            $header = $this->buildAssignmentRuleHeader($assignedRule, $protocol);
            $input->setHeaders($header);
        }
    }

    /**
     * @param string $ruleId
     * @param string $protocol
     *
     * @return array[]|string[]
     */
    private function buildAssignmentRuleHeader(string $ruleId, string $protocol): array
    {
        if ($protocol === 'soap') {
            return [
                'AssignmentRuleHeader' => [
                    'assignmentRuleId' => $ruleId,
                    'useDefaultRule' => false
                ]
            ];
        }

        return ['Sforce-Auto-Assign' => $ruleId];
    }
}
