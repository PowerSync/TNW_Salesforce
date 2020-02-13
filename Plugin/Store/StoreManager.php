<?php

namespace TNW\Salesforce\Plugin\Store;

class StoreManager extends \Magento\Store\Model\StoreManager
{

    /**
     * Fix for Magento v. 2.1, it cause error when we emulate admin store (id=0)
     *
     * @param \Magento\Store\Model\StoreManager $subject
     * @param callable $callback
     * @param null $storeId
     * @return mixed
     * @throws \ReflectionException
     */
    public function aroundGetStore(
        \Magento\Store\Model\StoreManager $subject,
        callable $callback,
        $storeId = null
    )
    {
        $currentStoreId = $subject->currentStoreId;

//        if (!isset($storeId) || '' === $storeId || $storeId === true) {
//            if (null !== $currentStoreId) {
//                $storeId = $currentStoreId;
//            }
//        }

        return $callback($storeId);
    }
}