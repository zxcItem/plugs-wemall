<?php

declare (strict_types=1);

namespace plugin\wemall\service;

use plugin\account\model\AccountUser;
use plugin\wemall\model\ShopConfigDiscount;
use plugin\wemall\model\ShopConfigLevel;
use plugin\shop\model\ShopOrder;
use plugin\wemall\model\ShopRebate;
use plugin\wemall\model\ShopConfigRebate;
use plugin\account\model\AccountRelation;
use plugin\payment\model\PaymentTransfer;
use think\admin\Exception;
use think\admin\extend\CodeExtend;
use think\admin\Library;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 实时发放订单返佣服务
 * @class UserRebate
 * @package plugin\wemall\service
 */
abstract class UserRebate
{
    public const pOrder = 'order';
    public const pFirst = 'first';
    public const pRepeat = 'repeat';
    public const pUpgrade = 'upgrade';
    public const pEqual = 'equal';

    // 奖励名称配置
    public const prizes = [
        self::pOrder   => '下单奖励',
        self::pFirst   => '首购奖励',
        self::pRepeat  => '复购奖励',
        self::pUpgrade => '升级奖励',
        self::pEqual   => '平推返佣',
    ];

    // 奖励描述配置
    public const pdescs = [
        '_' => '最高可获得%s的佣金~',
    ];

    /**
     * 用户编号
     * @var integer
     */
    private static $unid;

    /**
     * 用户数据
     * @var array
     */
    private static $user;

    /**
     * 用户关系
     * @var array
     */
    private static $rela0;

    /**
     * 直接代理
     * @var array
     */
    private static $rela1;

    /**
     * 间接代理
     * @var array
     */
    private static $rela2;

    /**
     * 间接2代理
     * @var array
     */
    private static $rela3;

    /**
     * 订单数据
     * @var array
     */
    private static $order;

    /**
     * 到账时间
     * @var integer
     */
    private static $status = 0;

    /**
     * 执行订单返佣处理
     * @param ShopOrder|string $order
     * @throws Exception
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function create($order)
    {
        // 获取订单数据
        self::$order = UserOrder::widthOrder($order)->toArray();
        if (empty(self::$order) || empty(self::$order['payment_status'])) {
            throw new Exception('订单不存在');
        }
        if (self::$order['amount_total'] <= 0) throw new Exception('订单金额为零');
        if (self::$order['rebate_amount'] <= 0) throw new Exception('订单返佣为零');

        // 获取用户数据
        self::$unid = intval(self::$order['unid']);
        self::$user = AccountUser::mk()->findOrEmpty(self::$unid)->toArray();
        self::$rela0 = AccountRelation::mk()->where(['unid' => self::$unid])->findOrEmpty()->toArray();
        if (empty(self::$user) || empty(self::$rela0)) throw new Exception('用户不存在');

        // 获取上一级代理数据
        if (self::$order['puid1'] > 0) {
            $map = ['unid' => self::$order['puid1']];
            self::$rela1 = AccountRelation::mk()->where($map)->findOrEmpty()->toArray();
            if (self::$rela1) throw new Exception('直接代理不存在');
        }

        // 获取上二级代理数据
        if (self::$order['puid2'] > 0) {
            $map = ['unid' => self::$order['puid2']];
            self::$rela2 = AccountRelation::mk()->where($map)->findOrEmpty()->toArray();
            if (self::$rela2) throw new Exception('上二代理不存在');
        }

        // 获取上三级代理数据
        if (self::$order['puid3'] > 0) {
            $map = ['unid' => self::$order['puid3']];
            self::$rela3 = AccountRelation::mk()->where($map)->findOrEmpty()->toArray();
            if (self::$rela3) throw new Exception('上三代理不存在');
        }

        // 批量查询规则并发放奖励
        $where = ['status' => 1, 'deleted' => 0];
        ShopConfigRebate::mk()->where($where)->select()->map(function (ShopConfigRebate $item) {
            $cfg = $item->toArray();
            // 返佣结算时间
            self::$status = empty($cfg['stype']) ? 1 : 0;
            // 检查关系链无代理的情况
            if ($cfg['p1_level'] < -1 && !empty(self::$rela1)) return;
            if ($cfg['p2_level'] < -1 && !empty(self::$rela2)) return;
            if ($cfg['p3_level'] < -1 && !empty(self::$rela3)) return;
            // 检查关系链代理等级匹配
            if ($cfg['p0_level'] > -1 && (empty(self::$rela0) || self::$rela0['level_code'] !== $cfg['p0_level'])) return;
            if ($cfg['p1_level'] > -1 && (empty(self::$rela1) || self::$rela1['level_code'] !== $cfg['p1_level'])) return;
            if ($cfg['p2_level'] > -1 && (empty(self::$rela2) || self::$rela2['level_code'] !== $cfg['p2_level'])) return;
            if ($cfg['p3_level'] > -1 && (empty(self::$rela3) || self::$rela3['level_code'] !== $cfg['p3_level'])) return;
            // 调用对应接口发放奖励
            if (method_exists(self::class, $method = "_{$cfg['type']}")) {
                Library::$sapp->log->notice("订单 " . self::$order['oroder_no'] . " 开始发放 {$cfg['code']}#[{$cfg['name']}] 奖励");
                foreach ([self::$rela0, self::$rela1, self::$rela2, self::$rela3] as $k => $v) if ($v) self::$method($cfg, $v, $k);
                Library::$sapp->log->notice("订单 " . self::$order['oroder_no'] . " 完成发放 {$cfg['code']}#[{$cfg['name']}] 奖励");
            }
        });
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
        /** @var ShopRebate $item */
        $map = [['status', '=', 0], ['deleted', '=', 0], ['order_no', 'like', "{$order->getAttr('order_no')}%"]];
        foreach (ShopRebate::mk()->where($map)->cursor() as $item) {
            $item->save(['status' => 1, 'remark' => '订单已确认收货！']);
            UserRebate::recount($item->getAttr('unid'));
        }
        return true;
    }

    /**
     * 取消订单发放返佣
     * @param ShopOrder|string $order
     * @return boolean
     * @throws Exception
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function cancel($order): bool
    {
        $order = UserOrder::widthOrder($order);
        if ($order->isEmpty() || $order->getAttr('status') > 0) {
            throw new Exception('订单状态异常！');
        }
        // 更新返佣记录
        $map = [['deleted', '=', 0], ['order_no', 'like', "{$order->getAttr('order_no')}%"]];
        foreach (ShopRebate::mk()->where($map)->cursor() as $item) {
            $item->save(['status' => 0, 'deleted' => 1, 'remark' => '订单已取消退回返佣！']);
            UserRebate::recount($item->getAttr('unid'));
        }
        return true;
    }

    /**
     * 同步刷新用户返佣
     * @param integer $unid 指定用户ID
     * @param array|null $data 非数组时更新数据
     * @return array [total, count, lock]
     */
    public static function recount(int $unid, ?array &$data = null): array
    {
        if ($isUpdate = !is_array($data)) $data = [];
        if ($unid > 0) {
            $count = PaymentTransfer::mk()->whereRaw("unid='{$unid}' and status>0")->sum('amount');
            $total = ShopRebate::mk()->whereRaw("unid='{$unid}' and status=1 and deleted=0")->sum('amount');
            $locks = ShopRebate::mk()->whereRaw("unid='{$unid}' and status=0 and deleted=0")->sum('amount');
            [$data['rebate_total'], $data['rebate_used'], $data['rebate_lock']] = [$total, $count, $locks];
            if ($isUpdate && ($user = AccountUser::mk()->findOrEmpty($unid))->isExists()) {
                $user->save(['extra' => array_merge($user->getAttr('extra'), $data)]);
            }
        } else {
            $count = PaymentTransfer::mk()->whereRaw("status > 0")->sum('amount');
            $total = ShopRebate::mk()->whereRaw("status = 1 and deleted = 0")->sum('amount');
            $locks = ShopRebate::mk()->whereRaw("status = 0 and deleted = 0")->sum('amount');
            [$data['rebate_total'], $data['rebate_used'], $data['rebate_lock']] = [$total, $count, $locks];
        }
        return [$total, $count, $locks];
    }

    /**
     * 获取等级佣金描述
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function levels(): array
    {
        // 解析商品折扣规则
        $discs = [];
        foreach (ShopConfigDiscount::items() as $v) {
            foreach ($v['items'] as $vv) $discs[$vv['level']][] = floatval($vv['discount']);
        }
        // 合并等级折扣及奖励
        $levels = ShopConfigLevel::items(null, '*');
        foreach ($levels as &$level) {
            $level['prizes'] = [];
            if (($disc = round(min($discs[$level['number']] ?? [100]))) < 100) $level['prizes'][] = [
                'type' => 0, 'value' => $disc, 'name' => '享折扣价', 'desc' => "最高可享受商品的 {$disc}% 折扣价购买~"
            ];
        }
        return array_values($levels);
    }

    /**
     * 下单支付奖励
     * @param array $cfg
     * @param array $rel
     * @param integer $idx
     * @return boolean
     */
    protected static function _order(array $cfg, array $rel, int $idx): bool
    {
        // 检查返佣是否已经发放
        if (empty($rel)) return false;
        $ono = self::$order['order_no'];
        $map = ['code' => md5("{$cfg['code']}#{$ono}#{$rel['unid']}#{$cfg['type']}")];
        if (ShopRebate::mk()->where($map)->findOrEmpty()->isExists()) return false;
        // 根据配置计算返佣数据
        $value = floatval($cfg["p{$idx}_reward_number"] ?: '0.00');
        if ($cfg["p{$idx}_reward_type"] == 0) {
            $val = $value;
            $name = sprintf('%s，每单 %s 元', $cfg['name'], $val);
        } elseif ($cfg["p{$idx}_reward_type"] == 1) {
            $val = floatval($value * self::$order['rebate_amount'] / 100);
            $name = sprintf('%s，订单金额 %s%%', $cfg['name'], $value);
        } elseif ($cfg["p{$idx}_reward_type"] == 2) {
            $val = floatval($value * self::$order['amount_profit'] / 100);
            $name = sprintf('%s，分佣金额 %s%%', $cfg['name'], $value);
        } else {
            return false;
        }
        // 写入返佣记录
        return self::wRebate(self::$rela1['unid'], $map, $name, $val);
    }

    /**
     * 用户首推奖励
     * @param array $cfg
     * @param array $rel
     * @param integer $idx
     * @return boolean
     */
    protected static function _frist(array $cfg, array $rel, int $idx): bool
    {
        // 是否首次购买
        $orders = ShopRebate::mk()->where(['order_unid' => self::$unid])->limit(2)->column('order_no');
        if (count($orders) > 1 || (count($orders) === 1 && !in_array(self::$order['order_no'], $orders))) return false;
        // 发放用户首推奖励
        return self::_order($cfg, $rel, $idx);
    }

    /**
     * 用户复购奖励
     * @param array $cfg
     * @param array $rel
     * @param integer $idx
     * @return bool
     */
    protected function _repeat(array $cfg, array $rel, int $idx): bool
    {
        // 是否复购购买
        $orders = ShopRebate::mk()->where(['order_unid' => self::$unid])->limit(2)->column('order_no');
        if (count($orders) < 1 || (count($orders) === 1 && in_array(self::$order['order_no'], $orders))) return false;
        // 发放用户复购奖励
        return self::_order($cfg, $rel, $idx);
    }

    /**
     * 用户升级奖励发放
     * @param array $cfg
     * @param array $rel
     * @param integer $idx
     * @return boolean
     */
    private static function _upgrade(array $cfg, array $rel, int $idx): bool
    {
        if (empty(self::$rela1)) return false;
        if (empty(self::$user['extra']['level_order']) || self::$user['extra']['level_order'] !== self::$order['order_no']) return false;
        return self::_order($cfg, $rel, $idx);
    }

    /**
     * 用户平推奖励发放
     * @param array $cfg
     * @param array $rel
     * @param integer $idx
     * @return boolean
     */
    protected static function _equal(array $cfg, array $rel, int $idx): bool
    {
        if (self::$rela0['level_code'] !== self::$rela1['level_code']) return false;
        return self::_order($cfg, $rel, $idx);
    }

    /**
     * 写入返佣记录
     * @param integer $unid 奖励用户
     * @param array $map 查询条件
     * @param string $name 奖励名称
     * @param float $amount 奖励金额
     * @return boolean
     */
    private static function wRebate(int $unid, array $map, string $name, float $amount): bool
    {
        return ShopRebate::mk()->save(array_merge([
            'unid'         => $unid,
            'date'         => date('Y-m-d'),
            'code'         => CodeExtend::uniqidDate(16, 'R'),
            'name'         => $name,
            'amount'       => $amount,
            'status'       => self::$status,
            'order_no'     => self::$order['order_no'],
            'order_unid'   => self::$order['unid'],
            'order_amount' => self::$order['amount_total'],
        ], $map));
    }
}