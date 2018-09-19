<?php
namespace TNW\Salesforce\Block\Wizard\Step;

/**
 * Class Test
 * @package TNW\Salesforce\Block\Wizard\Step
 */
class Test extends \Magento\Backend\Block\Template
{
    /**
     * @var null|\TNW\Salesforce\Model\TestCollection
     */
    protected $testCollection = null;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \TNW\Salesforce\Model\TestCollection $testCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \TNW\Salesforce\Model\TestCollection $testCollection,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->testCollection = $testCollection;
    }

    /**
     * @return array
     */
    public function getTests()
    {
        return $this->testCollection->getSalesforceDependencies();
    }

    /**
     * Get validation URL
     * @return string
     */
    public function getValidateTestsUrl()
    {
        return $this->getUrl('tnw_salesforce/wizard/tests');
    }
}
