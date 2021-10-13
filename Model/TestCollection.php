<?php
declare(strict_types=1);

namespace TNW\Salesforce\Model;

/**
 * Class TestCollection
 * @package TNW\Salesforce\Model
 */
class TestCollection
{
    /**
     * @var array({TEST NAMES})
     */
    protected $tests = array('Connection', 'License');

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $objectManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * Get required settings for salesforce
     *
     * @param array $testIds
     * @return array
     */
    public function getSalesforceDependencies(array $testIds = array()): array
    {
        $result = array();
        if (!$testIds) {
            $result = $this->getAllTests();
        } else {
            foreach ($testIds as $id) {
                $result[] = $this->test($id);
            }
        }

        return $result;
    }

    /**
     * Get test status
     *
     * @param string $testId
     * @return array
     */
    protected function test($testId): array
    {
        $result = array();
        /** @var $test TestInterface */
        $test = $this->objectManager->get('TNW\\Salesforce\\Model\\Test\\' . $testId);
        if ($test && $test instanceof TestInterface) {
            $test->execute();
            $result['id'] = $testId;
            $result['status'] = $test->getStatus();
            $result['label'] = $test->getLabel();
        }

        return $result;
    }

    /**
     * Retrieve all test and their statuses
     *
     * @return array
     */
    protected function getAllTests(): array
    {
        $result = array();
        foreach ($this->tests as $testType) {
            /** @var $test TestInterface */
            $test = $this->objectManager->get('TNW\\Salesforce\\Model\\Test\\' . $testType);
            if ($test && $test instanceof TestInterface) {
                $test->execute();
                $result[] = array(
                    'id' => $testType,
                    'status' => $test->getStatus(),
                    'label' => $test->getLabel()
                );
            }
        }

        return $result;
    }
}
