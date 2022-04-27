<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

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
