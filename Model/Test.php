<?php
namespace TNW\Salesforce\Model;

use Magento\Framework\DataObject;

/**
 * Class Test
 * @package TNW\Salesforce\Model
 */
abstract class Test extends DataObject implements TestInterface
{
    /**
     * Test Label to display
     * @var string
     */
    protected $testLabel = 'Some Text Label';

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * success test execution status
     */
    const STATUS_PASSED = 'passed';

    /**
     * failure test execution status
     */
    const STATUS_FAILED = 'failed';

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        return $this;
    }

    /**
     * Get test label
     *
     * @return string
     */
    public function getLabel()
    {
        return __($this->testLabel);
    }
}
