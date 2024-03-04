<?php

declare (strict_types=1);

namespace plugin\wemall\service;

use plugin\payment\model\PaymentRecord;
use plugin\payment\service\Payment;
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
     * 根据订单更新用户等级
     * @param string $orderNo
     * @return array|null [USER, ORDER, ENTRY]
     * @throws Exception
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function upgrade(string $orderNo): ?array
    {
        // 目标订单数据
        $map = [['order_no', '=', $orderNo], ['status', '>=', 4]];
        $order = ShopOrder::mk()->where($map)->findOrEmpty();
        if ($order->isEmpty()) return null;
        // 会员用户数据
        $map = ['unid' => $order['unid']];
        $user = AccountRelation::mk()->where($map)->findOrEmpty();
        if ($user->isEmpty()) return null;
        // 更新入会资格
        $entry = self::_vipEntry($order['unid']);
        // 尝试绑定代理
        if (empty($user['puid1']) && ($order['puid1'] > 0 || $user['puid0'] > 0)) {
            $puid1 = $order['puid1'] > 0 ? $order['puid1'] : $user['puid0'];
            UserUpgrade::bindAgent($user['id'], $puid1);
        }
        // 重置订单推荐
        if ($user->refresh() && $user['puid1'] > 0) {
            $order->save(['puid1' => $user['puid1'], 'puid2' => $user['puid2']]);
        }
        // 刷新用户等级
        UserUpgrade::upgrade($user['unid'], true, $orderNo);
        // 返回操作数据
        return [$user->toArray(), $order->toArray(), $entry];
    }

    /**
     * 刷新用户入会礼包
     * @param integer $unid 用户UID
     * @return integer
     * @throws DbException
     */
    private static function _vipEntry(int $unid): int
    {
        // 检查入会礼包
        $query = ShopOrder::mk()->alias('a')->join([ShopOrderItem::mk()->getTable() => 'b'], 'a.order_no=b.order_no');
        $entry = $query->where("a.unid={$unid} and a.status>=4 and a.payment_status=1 and b.level_upgrade>-1")->count() ? 1 : 0;
        // 用户最后支付时间
        $lastMap = [['unid', '=', $unid], ['status', '>=', 4], ['payment_status', '=', 1]];
        $lastDate = ShopOrder::mk()->where($lastMap)->order('payment_time desc')->value('payment_time');
        // 更新用户支付信息
        AccountRelation::mk()->where(['unid' => $unid])->update(['buy_vip_entry' => $entry, 'buy_last_date' => $lastDate]);
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
     * @param ShopOrder $order 订单模型
     * @param PaymentRecord $payment 支付行为记录
     * @return array|void|null
     * @throws Exception
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @remark 订单状态(0已取消,1预订单,2待支付,3待审核,4待发货,5已发货,6已收货,7已评论)
     */
    public static function payment(ShopOrder $order, PaymentRecord $payment)
    {
        $orderNo = $payment->getAttr('order_no');
        $paidAmount = Payment::paidAmount($orderNo, true);
        // 提交支付凭证，只需更新订单状态
        $isVoucher = $payment->getAttr('channel_type') === Payment::VOUCHER;
        // 发起订单退款，标记订单已取消
        if (empty($paidAmount) && $payment->getAttr('refund_status')) {
            try { /* 取消订单余额积分奖励及反拥 */
                static::cancel($orderNo, true);
            } catch (\Exception $exception) {
                trace_file($exception);
            }
            return self::upgrade($orderNo);
        }

        // 订单已经支付完成
        if ($paidAmount >= $order->getAttr('amount_real')) {
            try { /* 订单返佣处理 */
                UserRebate::create($orderNo);
            } catch (\Exception $exception) {
                trace_file($exception);
            }
            return self::upgrade($orderNo);
        }

        // 凭证支付审核被拒绝
        if ($isVoucher && $payment->getAttr('audit_status') !== 1) {
            return self::upgrade($orderNo);
        }
    }

    /**
     * 验证订单取消余额
     * @param string $orderNo 订单单号
     * @param boolean $syncRebate 更新返利
     * @return string
     * @throws Exception
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function cancel(string $orderNo, bool $syncRebate = false): string
    {
        $map = ['status' => 0, 'order_no' => $orderNo];
        $order = ShopOrder::mk()->where($map)->findOrEmpty();
        if ($order->isEmpty()) throw new Exception('订单状态异常');
        $code = "CZ{$order['order_no']}";
        // 取消订单返佣
        $syncRebate && UserRebate::cancel($orderNo);
        return $code;
    }

    /**
     * 订单支付发放余额
     * @param string $orderNo
     * @param bool $unlock
     * @return string
     * @throws DataNotFoundException
     * @throws DbException
     * @throws Exception
     * @throws ModelNotFoundException
     */
    public static function confirm(string $orderNo,bool $unlock = false): string
    {
        $map = [['status', '>=', 4], ['order_no', '=', $orderNo]];
        $order = ShopOrder::mk()->where($map)->findOrEmpty();
        if ($order->isEmpty()) throw new Exception('订单状态异常');
        $code = "CZ{$order['order_no']}";
        // 升级用户等级
        UserUpgrade::upgrade($order->getAttr('unid'), true, $orderNo);
        // 返回奖励单号
        return $code;
    }
}