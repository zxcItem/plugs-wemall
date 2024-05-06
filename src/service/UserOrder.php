<?php

declare (strict_types=1);

namespace plugin\wemall\service;

use plugin\payment\model\PaymentRecord;
use plugin\payment\service\Payment;
use plugin\shop\service\UserReward;
use plugin\wemall\model\ShopConfigDiscount;
use plugin\shop\model\ShopOrder;
use plugin\shop\model\ShopOrderItem;
use plugin\account\model\AccountRelation;
use think\admin\Exception;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 商城订单数据服务
 * @class UserOrder
 * @package plugin\wemall\service
 */
class UserOrder
{

    /**
     * 获取订单模型
     * @param ShopOrder|string $order
     * @param ?integer $unid 动态绑定变量
     * @param ?string $orderNo 动态绑定变量
     * @throws Exception
     */
    public static function widthOrder($order, ?int &$unid = 0, ?string &$orderNo = ''): ShopOrder
    {
        if (is_string($order)) {
            $order = ShopOrder::mk()->where(['order_no' => $order])->findOrEmpty();
        }
        if ($order instanceof ShopOrder) {
            [$unid, $orderNo] = [intval($order->getAttr('unid')), $order->getAttr('order_no')];
            return $order;
        }
        throw new Exception("无效订单对象！");
    }

    /**
     * 根据订单更新用户等级
     * @param string $orderNo
     * @return array|null [USER, ORDER, ENTRY]
     * @throws Exception
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function upgrade($order): ?array
    {
        // 目标订单数据
        $order = self::widthOrder($order);
        if ($order->isEmpty() || $order->getAttr('status') < 4) return null;
        // 会员用户数据
        $where = ['unid' => $order->getAttr('unid')];
        $relation = AccountRelation::mk()->where($where)->findOrEmpty();
        if ($relation->isEmpty()) return null;
        // 更新入会资格
        $entry = self::_vipEntry($relation);
        // 尝试绑定代理
        if (empty($relation['puids']) && $order->getAttr('puid1') > 0) {
            $puid1 = $order->getAttr('puid1') > 0 ? $order->getAttr('puid1') : $relation['puid1'];
            UserUpgrade::bindAgent($relation['unid'], intval($puid1));
        }
        // 重置订单推荐
        if ($relation->refresh() && $relation['puid1'] > 0) {
            $order->save(['puid1' => $relation['puid1'], 'puid2' => $relation['puid2'], 'puid3' => $relation['puid3']]);
        }
        // 刷新用户等级
        UserUpgrade::upgrade($relation['unid'], true, $order->getAttr('order_no'));
        // 返回操作数据
        return [$relation->toArray(), $order->toArray(), $entry];
    }

    /**
     * 刷新用户入会礼包
     * @param AccountRelation $relation
     * @return integer
     * @throws DbException
     */
    private static function _vipEntry(AccountRelation $relation): int
    {
        $unid = intval($relation->getAttr('unid'));
        // 检查入会礼包
        $query = ShopOrder::mk()->alias('a')->join([ShopOrderItem::mk()->getTable() => 'b'], 'a.order_no=b.order_no');
        $entry = $query->where("a.unid={$unid} and a.status>=4 and a.payment_status=1 and b.level_upgrade>-1")->count() ? 1 : 0;
        // 用户最后支付时间
        $lastMap = [['unid', '=', $unid], ['status', '>=', 4], ['payment_status', '=', 1]];
        $lastDate = ShopOrder::mk()->where($lastMap)->order('payment_time desc')->value('payment_time');
        // 更新用户入会信息
        $relation->save(['buy_vip_entry' => $entry, 'buy_last_date' => $lastDate]);
        return $entry;
    }

    /**
     * 获取等级折扣比例
     * @param integer $disId 折扣方案ID
     * @param integer $levelCode 等级序号
     * @param float $disRate 默认比例
     * @return array [方案编号, 折扣比例]
     */
    public static function discount(int $disId, int $levelCode, float $disRate = 100.00): array
    {
        if ($disId > 0) {
            $where = ['id' => $disId, 'status' => 1, 'deleted' => 0];
            $discount = ShopConfigDiscount::mk()->where($where)->findOrEmpty();
            if ($discount->isExists()) foreach ($discount['items'] as $vo) {
                if ($vo['level'] == $levelCode) $disRate = floatval($vo['discount']);
            }
        }
        return [$disId, $disRate];
    }

    /**
     * 更新订单支付状态
     * @param ShopOrder|string $order 订单模型
     * @param PaymentRecord $payment 支付行为记录
     * @return array|bool|string|void|null
     * @throws Exception
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @remark 订单状态(0已取消,1预订单,2待支付,3待审核,4待发货,5已发货,6已收货,7已评论)
     */
    public static function change($order, PaymentRecord $payment)
    {
        $order = self::widthOrder($order);
        if ($order->isEmpty()) return null;

        // 同步订单支付统计
        $ptotal = Payment::totalPaymentAmount($payment->getAttr('order_no'));
        $order->appendData([
            'payment_time'    => $payment->getAttr('create_time'),
            'payment_amount'  => $ptotal['amount'] ?? 0,
            'amount_payment'  => $ptotal['payment'] ?? 0,
            'amount_balance'  => $ptotal['balance'] ?? 0,
            'amount_integral' => $ptotal['integral'] ?? 0,
        ], true);

        // 订单已经支付完成
        if ($order->getAttr('payment_amount') >= $order->getAttr('amount_real')) {
            // 已完成支付，更新订单状态
            $status = $order->getAttr('delivery_type') ? 4 : 5;
            $order->save(['status' => $status, 'payment_status' => 1]);
            // 确认完成支付，发放余额积分奖励及升级返佣
            return static::confirm($order);
        }

        // 退款或部分退款，仅更新订单支付统计
        if ($payment->getAttr('refund_status')) {
            return $order->save();
        }

        // 提交支付凭证，只需更新订单状态为【待审核】
        $isVoucher = $payment->getAttr('channel_type') === Payment::VOUCHER;
        if ($isVoucher && $payment->getAttr('audit_status') === 1) return $order->save([
            'status' => 3, 'payment_status' => 1,
        ]);

        // 凭证支付审核被拒绝，订单回滚到未支付状态
        if ($isVoucher && $payment->getAttr('audit_status') === 0) {
            if ($order->getAttr('status') === 3) $order->save(['status' => 2]);
            return self::upgrade($order);
        } else {
            $order->save();
        }
    }

    /**
     * 取消订单撤销奖励
     * @param ShopOrder|string $order
     * @param boolean $setRebate 更新返佣
     * @return string
     */
    public static function cancel($order, bool $setRebate = false): string
    {
        try { /* 创建用户奖励 */
            $order = UserReward::cancel($order, $code);
        } catch (\Exception $exception) {
            trace_file($exception);
        }
        if ($setRebate) try { /* 订单返佣处理 */
            UserRebate::cancel($order);
        } catch (\Exception $exception) {
            trace_file($exception);
        }
        try { /* 升级用户等级 */
            UserUpgrade::upgrade(intval($order->getAttr('unid')));
        } catch (\Exception $exception) {
            trace_file($exception);
        }
        return $code;
    }

    /**
     * 支付成功发放奖励
     * @param ShopOrder|string $order
     * @return string
     */
    public static function confirm($order): string
    {
        try { /* 创建用户奖励 */
            UserReward::create($order, $code);
        } catch (\Exception $exception) {
            trace_file($exception);
        }
        try { /* 订单返佣处理 */
            UserRebate::create($order);
        } catch (\Exception $exception) {
            trace_file($exception);
        }
        try { /* 升级用户等级 */
            self::upgrade($order);
        } catch (\Exception $exception) {
            trace_file($exception);
        }
        // 返回奖励单号
        return $code;
    }
}