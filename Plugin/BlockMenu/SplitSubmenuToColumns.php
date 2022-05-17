<?php
declare(strict_types=1);

namespace TNW\Salesforce\Plugin\BlockMenu;

use Magento\Backend\Block\Menu;
use Magento\Backend\Model\Menu as MenuModel;
use Magento\Backend\Model\Menu\Item;

/**
 * Split submenu to columns plugin.
 */
class SplitSubmenuToColumns
{
    private const VENDOR_PREFIX = 'TNW_';
    private const MAIN_MENU_ID = 'TNW_Salesforce::salesforce';

    /**
     * Split to columns submenu if the first submenu section contains more allowed by $limit items.
     *
     * @param Menu      $subject
     * @param MenuModel $modelMenu
     * @param int       $level
     * @param int       $limit
     *
     * @return array
     */
    public function beforeRenderNavigation(Menu $subject, MenuModel $modelMenu, $level = 0, $limit = 0)
    {
        if ($level !== 1 || !$modelMenu->count()) {
            return null;
        }

        /** @var Item $item */
        $item = $modelMenu->getIterator()->current();
        $id = $item->getId();
        if (strpos($id, self::VENDOR_PREFIX) === false) {
            return null;
        }

        $parents = $subject->getMenuModel()->getParentItems($id);
        $parentMenuId = $parents ? reset($parents)->getId() : null;
        if ($parentMenuId !== self::MAIN_MENU_ID) {
            return null;
        }

        $colBrakes = $this->columnBrake($modelMenu, $limit);

        return [$modelMenu, $level, $limit, $colBrakes];
    }

    /**
     * Building Array with Column Brake Stops.
     *
     * @param MenuModel $items
     * @param int       $limit
     *
     * @return array
     */
    private function columnBrake(MenuModel $items, int $limit): array
    {
        $total = $this->countItems($items);
        if ($total <= $limit) {
            return [];
        }

        $max = ceil($total / ceil($total / $limit));
        $result[] = ['total' => $total, 'max' => $max];
        $count = 0;
        foreach ($items as $item) {
            $place = $this->countItems($item->getChildren()) + 1;
            $count += $place;
            if ($place - $max > $limit - $max || $count - $max > $limit - $max) {
                $colbrake = true;
                $count = $place;
            } else {
                $colbrake = false;
            }

            $result[] = ['place' => $place, 'colbrake' => $colbrake];
        }

        return $result;
    }

    /**
     * Count All Subnavigation Items.
     *
     * @param MenuModel $items
     *
     * @return int
     */
    private function countItems(MenuModel $items): int
    {
        $total = count($items);
        foreach ($items as $item) {
            /** @var Item $item */
            if ($item->hasChildren()) {
                $total += $this->countItems($item->getChildren());
            }
        }

        return $total;
    }
}
