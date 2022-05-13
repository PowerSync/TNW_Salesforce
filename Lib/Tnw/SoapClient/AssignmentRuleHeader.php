<?php
namespace TNW\Salesforce\Lib\Tnw\SoapClient;

/**
 * AssignmentRuleHeader class to set rule during update Lead
 *
 * Class AssignmentRuleHeader
 */
class AssignmentRuleHeader
{
    /** @var string|int */
    public $assignmentRuleId;

    /** @var bool */
    public $useDefaultRuleFlag;

    /**
     * Constructor.  Only one param can be set.
     *
     * @param string|int     $id   AssignmentRuleId
     * @param boolean|null $flag UseDefaultRule flag
     */
    public function __construct($id = null, $flag = null)
    {
        if ($id !== null) {
            $this->assignmentRuleId = $id;
        }

        if ($flag !== null) {
            $this->useDefaultRuleFlag = $flag;
        }
    }
}
