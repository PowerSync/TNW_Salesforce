<?php
namespace TNW\Salesforce\Lib\Tnw\SoapClient;
/**
 * AssignmentRuleHeader class to set rule during update Lead
 *
 * Class AssignmentRuleHeader
 */
class AssignmentRuleHeader
{
    // int
    public $assignmentRuleId;
    // boolean
    public $useDefaultRuleFlag;

    /**
     * Constructor.  Only one param can be set.
     *
     * @param int $id AssignmentRuleId
     * @param boolean $flag UseDefaultRule flag
     */
    public function __construct($id = null, $flag = null)
    {
        if ($id != null) {
            $this->assignmentRuleId = $id;
        }
        if ($flag != null) {
            $this->useDefaultRuleFlag = $flag;
        }
    }
}
