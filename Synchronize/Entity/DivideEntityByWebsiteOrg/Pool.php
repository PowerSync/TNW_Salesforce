<?php
namespace TNW\Salesforce\Synchronize\Entity\DivideEntityByWebsiteOrg;

use Magento\Framework\Exception\LocalizedException;
use TNW\Salesforce\Synchronize\Entity\DivideEntityByWebsiteOrg;

class Pool
{
    /**
     * @var DivideEntityByWebsiteOrg[]
     */
    private $dividers;

    public function __construct(
        array $dividers
    ) {
        $this->dividers = $dividers;
    }

    /**
     * Get processing entity depend on object type
     *
     * @param string $groupCode
     * @return DivideEntityByWebsiteOrg
     * @throws LocalizedException
     */
    public function getDividerByGroupCode($groupCode)
    {
        if (empty($this->dividers[$groupCode])) {
            throw new LocalizedException(__("Invalid group code: '%1'", $groupCode));
        }

        return $this->dividers[$groupCode];
    }
}
