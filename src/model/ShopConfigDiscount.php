<?php


declare (strict_types=1);

namespace plugin\wemall\model;

use plugin\account\model\Abs;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 用户优惠方案模型
 * @class ShopConfigDiscount
 * @package plugin\wemall\model
 */
class ShopConfigDiscount extends Abs
{

    /**
     * 获取折扣方案
     * @param boolean $allow
     * @return array[]
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function items(bool $allow = false): array
    {
        $query = self::mk()->where(['status' => 1, 'deleted' => 0]);
        $items = $allow ? ['0' => ['id' => '0', 'name' => '无折扣']] : [];
        return array_merge($items,$query->order('sort desc,id desc')->field('id,name,items')->select()->toArray());
    }

    /**
     * 格式化等级规则
     * @param mixed $value
     * @return mixed
     */
    public function getItemsAttr($value): array
    {
        return $this->getExtraAttr($value);
    }

    public function setItemsAttr($value): string
    {
        return $this->setExtraAttr($value);
    }
}