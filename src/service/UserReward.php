<?php

declare (strict_types=1);

namespace plugin\wemall\service;

use plugin\payment\service\BalanceService;
use plugin\payment\service\IntegralService;
use plugin\shop\model\ShopOrder;
use think\admin\Exception;

/**
 * 商城订单奖励
 * @class UserReward
 * @package plugin\wemall\service
 */
abstract class UserReward
{

    /**
     * 创建用户奖励
     * @param ShopOrder|string $order
     * @param string|null $code 奖励编号
     * @return ShopOrder
     * @throws Exception
     */
    public static function create($order, ?string &$code = ''): ShopOrder
    {
        $order = UserOrder::widthOrder($order, $unid, $orderNo);
        if ($order->isEmpty() && $order->getAttr('status') < 4) {
            throw new Exception('订单状态异常');
        }
        // 生成奖励编号
        $code = $code ?: "CZ{$order->getAttr('order_no')}";
        // 确认奖励余额
        if ($order->getAttr('reward_balance') > 0) {
            $remark = "来自订单 {$order->getAttr('order_no')} 奖励 {$order->getAttr('reward_balance')} 余额";
            BalanceService::create($order->getAttr('unid'), $code, '购物奖励余额', floatval($order->getAttr('reward_balance')), $remark, true);
        }
        // 确认奖励积分
        if ($order->getAttr('reward_integral') > 0) {
            $remark = "来自订单 {$order->getAttr('order_no')} 奖励 {$order->getAttr('reward_integral')} 积分";
            IntegralService::create($order->getAttr('unid'), $code, '购物奖励积分', floatval($order->getAttr('reward_integral')), $remark, true);
        }
        // 返回订单模型
        return $order;
    }

    /**
     * 确认发放奖励
     * @param ShopOrder|string $order
     * @param string|null $code 奖励编号
     * @return ShopOrder
     * @throws Exception
     */
    public static function confirm($order, ?string &$code = ''): ShopOrder
    {
        $order = UserOrder::widthOrder($order, $unid, $orderNo);
        if ($order->isEmpty() && $order->getAttr('status') < 4) {
            throw new Exception('订单状态异常');
        }
        // 生成奖励编号
        $code = $code ?: "CZ{$order->getAttr('order_no')}";
        BalanceService::unlock($code) && IntegralService::unlock($code);
        // 返回订单模型
        return $order;
    }

    /**
     * 取消订单奖励
     * @param ShopOrder|string $order
     * @param string|null $code 奖励编号
     * @return ShopOrder
     * @throws Exception
     */
    public static function cancel($order, ?string &$code = ''): ShopOrder
    {
        $order = UserOrder::widthOrder($order, $unid, $orderNo);
        if ($order->isEmpty() && $order->getAttr('status') > 0) {
            throw new Exception('订单状态异常');
        }
        // 生成奖励编号
        $code = $code ?: "CZ{$order->getAttr('order_no')}";
        // 取消余额奖励 及 积分奖励
        if ($order->getAttr('reward_balance') > 0) BalanceService::cancel($code);
        if ($order->getAttr('reward_integral') > 0) IntegralService::cancel($code);
        // 返回订单模型
        return $order;
    }
}