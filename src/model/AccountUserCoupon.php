<?php

declare (strict_types=1);

namespace plugin\wemall\model;

use think\model\relation\HasOne;

/**
 * 用户卡券数据
 * @class PluginWemallUserCoupon
 * @package plugin\wemall\model
 */
class AccountUserCoupon extends AbsUser
{

    /**
     * 关联卡券
     * @return \think\model\relation\HasOne
     */
    public function coupon(): HasOne
    {
        return $this->hasOne(ShopConfigCoupon::class, 'id', 'coid');
    }

    /**
     * 绑定卡券
     * @return HasOne
     */
    public function bindCoupon(): HasOne
    {
        return $this->coupon()->bind([
            'coupon_name'    => 'name',
            'coupon_amount'  => 'amount',
            'coupon_status'  => 'status',
            'coupon_deleted' => 'deleted',
            'limit_times'    => 'limit_times',
            'limit_amount'   => 'limit_amount',
            'limit_levels'   => 'limit_levels',
            'expire_days'    => 'expire_days',
        ]);
    }

    /**
     * 数据转换格式
     * @return array
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        if (isset($data['type'])) {
            $data['type_name'] = ShopConfigCoupon::types[$data['type']] ?? $data['type'];
        }
        return $data;
    }
}