<?php


declare (strict_types=1);

namespace plugin\wemall\model;

use plugin\shop\model\AbsUser;

/**
 * 用户优惠方案数据
 * @class PluginWemallConfigDiscount
 * @package plugin\wemall\model
 */
class PluginWemallConfigDiscount extends AbsUser
{

    /**
     * 获取折扣方案
     * @param boolean $allow
     * @return array[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function items(bool $allow = false): array
    {
        $query = self::mk()->where(['status' => 1, 'deleted' => 0]);
        $items = $query->order('sort desc,id desc')->field('id,name,items')->select()->toArray();
        if ($allow) array_unshift($items, ['id' => '0', 'name' => '无折扣']);
        return $items;
    }

    /**
     * 格式化等级规则
     * @param mixed $value
     * @return array
     */
    public function getItemsAttr($value): array
    {
        return $this->getExtraAttr($value);
    }

    /**
     * @param mixed $value
     * @return string
     */
    public function setItemsAttr($value): string
    {
        return $this->setExtraAttr($value);
    }
}