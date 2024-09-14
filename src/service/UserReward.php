<?php

declare (strict_types=1);

namespace plugin\wemall\service;

use plugin\fund\service\Balance;
use plugin\fund\service\Integral;
use plugin\shop\model\PluginShopOrder;
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
     * @param PluginShopOrder|string $order
     * @param string|null $code 奖励编号
     * @return PluginShopOrder
     * @throws \think\admin\Exception
     */
    public static function create($order, ?string &$code = ''): PluginShopOrder
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
            Balance::create($order->getAttr('unid'), $code, '购物奖励余额', floatval($order->getAttr('reward_balance')), $remark, true);
        }
        // 确认奖励积分
        if ($order->getAttr('reward_integral') > 0) {
            $remark = "来自订单 {$order->getAttr('order_no')} 奖励 {$order->getAttr('reward_integral')} 积分";
            Integral::create($order->getAttr('unid'), $code, '购物奖励积分', floatval($order->getAttr('reward_integral')), $remark, true);
        }
        // 返回订单模型
        return $order;
    }

    /**
     * 确认发放奖励
     * @param PluginShopOrder|string $order
     * @param string|null $code 奖励编号
     * @return \plugin\shop\model\PluginShopOrder
     * @throws \think\admin\Exception
     */
    public static function confirm($order, ?string &$code = ''): PluginShopOrder
    {
        $order = UserOrder::widthOrder($order, $unid, $orderNo);
        if ($order->isEmpty() && $order->getAttr('status') < 4) {
            throw new Exception('订单状态异常');
        }
        // 生成奖励编号
        $code = $code ?: "CZ{$order->getAttr('order_no')}";
        Balance::unlock($code) && Integral::unlock($code);
        // 返回订单模型
        return $order;
    }

    /**
     * 取消订单奖励
     * @param PluginShopOrder|string $order
     * @param string|null $code 奖励编号
     * @return PluginShopOrder
     * @throws \think\admin\Exception
     */
    public static function cancel($order, ?string &$code = ''): PluginShopOrder
    {
        $order = UserOrder::widthOrder($order, $unid, $orderNo);
        if ($order->isEmpty() && $order->getAttr('status') > 0) {
            throw new Exception('订单状态异常');
        }
        // 生成奖励编号
        $code = $code ?: "CZ{$order->getAttr('order_no')}";
        // 取消余额奖励 及 积分奖励
        if ($order->getAttr('reward_balance') > 0) Balance::cancel($code);
        if ($order->getAttr('reward_integral') > 0) Integral::cancel($code);
        // 返回订单模型
        return $order;
    }
}