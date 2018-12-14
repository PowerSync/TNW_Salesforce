<?php
namespace TNW\Salesforce\Model\ResourceModel\Objects\Customer;

use TNW\Salesforce\Model\ResourceModel\Objects\SelectAbstract;

class WebsiteSelect extends SelectAbstract
{
    /**
     * @inheritdoc
     */
    public function build()
    {
        return 'main_table.website_id';
    }
}
