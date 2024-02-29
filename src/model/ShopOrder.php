<?php

declare (strict_types=1);

namespace plugin\wemall\model;

use plugin\account\model\Abs;
use plugin\account\model\AccountUser;
use plugin\payment\model\PaymentRecord;
use think\model\relation\HasMany;
use think\model\relation\HasOne;

/**
 * 商城订单主模型
 * @class ShopOrder
 * @package plugin\wemall\model
 */
class ShopOrder extends Abs
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
     * 关联推荐用户
     * @return HasOne
     */
    public function from(): HasOne
    {
        return $this->hasOne(AccountUser::class, 'id', 'puid1');
    }

    /**
     * 关联商品数据
     * @return HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(ShopOrderItem::class, 'order_no', 'order_no');
    }

    /**
     * 关联支付数据
     * @return HasOne
     */
    public function payment(): HasOne
    {
        return $this->hasOne(PaymentRecord::class, 'order_no', 'order_no')->where([
            'payment_status' => 1,
        ]);
    }

    /**
     * 关联支付记录
     * @return HasMany
     */
    public function payments(): HasMany
    {
        return $this->hasMany(PaymentRecord::class, 'order_no', 'order_no')->order('id desc');
    }

    /**
     * 关联收货地址
     * @return HasOne
     */
    public function address(): HasOne
    {
        return $this->hasOne(ShopOrderSend::class, 'order_no', 'order_no');
    }

    /**
     * 关联发货信息
     * @return HasOne
     */
    public function sender(): HasOne
    {
        return $this->hasOne(ShopOrderSend::class, 'order_no', 'order_no');
    }

    /**
     * 格式化支付通道
     * @param mixed $value
     * @return array
     */
    public function getPaymentAllowsAttr($value): array
    {
        $payments = is_string($value) ? str2arr($value) : [];
        return in_array('all', $payments) ? ['all'] : $payments;
    }

    /**
     * 时间格式处理
     * @param mixed $value
     * @return string
     */
    public function getPaymentTimeAttr($value): string
    {
        return $this->getCreateTimeAttr($value);
    }

    /**
     * 时间格式处理
     * @param mixed $value
     * @return string
     */
    public function setPaymentTimeAttr($value): string
    {
        return $this->setCreateTimeAttr($value);
    }
}