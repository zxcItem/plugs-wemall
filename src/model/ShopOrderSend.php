<?php

declare (strict_types=1);

namespace plugin\wemall\model;

use plugin\account\model\Abs;
use plugin\account\model\AccountUser;
use think\model\relation\HasOne;

/**
 * 商城订单发货模型
 * @class ShopOrderSend
 * @package plugin\wemall\model
 */
class ShopOrderSend extends Abs
{
    /**
     * 关联用户数据
     * @return HasOne
     */
    public function user(): HasOne
    {
        return $this->hasOne(AccountUser::class, 'id', 'unid');
    }

    /**
     * 关联订单数据
     * @return HasOne
     */
    public function main(): HasOne
    {
        return $this->hasOne(ShopOrder::class, 'order_no', 'order_no')->with(['items']);
    }
}