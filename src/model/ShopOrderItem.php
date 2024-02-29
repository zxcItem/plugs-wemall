<?php

declare (strict_types=1);

namespace plugin\wemall\model;

use plugin\account\model\Abs;
use think\model\relation\HasOne;

/**
 * 商城订单详情模型
 * @class ShopOrderItem
 * @package plugin\wemall\model
 */
class ShopOrderItem extends Abs
{
    /**
     * 关联商品信息
     * @return HasOne
     */
    public function goods(): HasOne
    {
        return $this->hasOne(ShopGoods::class, 'code', 'gcode');
    }
}