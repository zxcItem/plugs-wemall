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
use plugin\wemall\model\ShopRebate;
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
     * @param $order
     * @return array|null [USER, ORDER, ENTRY]
     * @throws DataNotFoundException
     * @throws DbException
     * @throws Exception
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
     * 确认收货订单返佣
     * @param ShopOrder|string $order
     * @return boolean
     * @throws Exception
     */
    public static function confirm(string $order): bool
    {
        $order = UserOrder::widthOrder($order);
        if ($order->isEmpty() || $order->getAttr('status') < 6) {
            throw new Exception('订单状态异常！');
        }
        $map = [['status', '=', 0], ['deleted', '=', 0], ['order_no', 'like', "{$order}%"]];
        foreach (ShopRebate::mk()->where($map)->cursor() as $item) {
            $item->save(['status' => 1, 'remark' => '订单已确认收货！']);
            UserRebate::recount($item->getAttr('unid'));
        }
        return true;
    }
}