<?php

declare (strict_types=1);

namespace plugin\wemall\service;

use plugin\account\model\AccountUser;
use plugin\payment\service\BalanceService;
use plugin\wemall\model\ShopConfigDiscount;
use plugin\wemall\model\ShopConfigLevel;
use plugin\shop\model\ShopOrder;
use plugin\shop\model\ShopOrderItem;
use plugin\wemall\model\ShopUserRebate;
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
class UserRebate
{
    public const pfirst = 'first';
    public const pRepeat = 'repeat';
    public const pDirect = 'direct';
    public const pIndirect = 'indirect';
    public const pMargin = 'margin';
    public const pEqual = 'equal';
    public const pManage = 'manage';
    public const pUpgrade = 'upgrade';

    // 奖励名称配置
    public const prizes = [
        self::pfirst    => '首推奖励',
        self::pRepeat   => '复购奖励',
        self::pDirect   => '直属团队',
        self::pIndirect => '间接团队',
        self::pMargin   => '差额奖励',
        self::pManage   => '管理奖励',
        self::pUpgrade  => '升级奖励',
        self::pEqual    => '平推返佣',
    ];

    public const prefix = [
        self::pfirst    => 'ST',
        self::pRepeat   => 'FG',
        self::pDirect   => 'ZS',
        self::pIndirect => 'JJ',
        self::pMargin   => 'CE',
        self::pManage   => 'GL',
        self::pUpgrade  => 'SJ',
        self::pEqual    => 'PT',
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
     * 订单数据
     * @var array
     */
    private static $order;

    /**
     * 奖励到账时机
     * @var integer
     */
    protected static $status;

    /**
     * 执行订单返佣处理
     * @param string $orderNo
     * @throws Exception
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function create(string $orderNo)
    {
        // 返佣奖励到账时机 ( 1.支付后到账，2.确认后到账 )
        self::$status = self::config('settl_type') > 1 ? 0 : 1;

        // 获取订单数据
        $map = ['order_no' => $orderNo, 'payment_status' => 1];
        self::$order = ShopOrder::mk()->where($map)->findOrEmpty()->toArray();
        if (empty(self::$order)) throw new Exception('订单不存在');
        if (self::$order['amount_total'] <= 0) throw new Exception('订单金额为零');
        if (self::$order['rebate_amount'] <= 0) throw new Exception('订单返佣为零');

        // 获取用户数据
        self::$unid = intval(self::$order['unid']);
        self::$user = AccountUser::mk()->findOrEmpty(self::$unid)->toArray();
        self::$rela0 = AccountRelation::mk()->where(['unid' => self::$unid])->findOrEmpty()->toArray();
        if (empty(self::$user) || empty(self::$rela0)) throw new Exception('用户不存在');

        // 获取直接代理数据
        if (self::$order['puid1'] > 0) {
            $map = ['unid' => self::$order['puid1']];
            self::$rela1 = AccountRelation::mk()->where($map)->findOrEmpty()->toArray();
            if (empty(self::$rela1)) throw new Exception('直接代理不存在');
        }

        // 获取间接代理数据
        if (self::$order['puid2'] > 0) {
            $map = ['unid' => self::$order['puid2']];
            self::$rela2 = AccountRelation::mk()->where($map)->findOrEmpty()->toArray();
            if (empty(self::$rela2)) throw new Exception('间接代理不存在');
        }
        // 批量发放配置奖励
        foreach (self::prizes as $k => $v) if (method_exists(static::class, $k)) {
            Library::$sapp->log->notice("订单 {$orderNo} 开始发放 [{$k}] {$v}");
            self::{strtolower($k)}($orderNo);
            Library::$sapp->log->notice("订单 {$orderNo} 完成发放 [{$k}] {$v}");
        }
        // 刷新用户返佣统计
        self::recount(self::$unid);
    }

    /**
     * 确认收货订单返佣
     * @param string $orderNo
     * @return array|void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws Exception
     * @throws ModelNotFoundException
     */
    public static function confirm(string $orderNo)
    {
        $map = [['status', '>=', 4], ['order_no', '=', $orderNo]];
        $order = ShopOrder::mk()->where($map)->findOrEmpty();
        if ($order->isEmpty()) return [0, '需处理的订单状态异常！'];
        $map = [['status', '=', 0], ['deleted', '=', 0], ['order_no', 'like', "{$orderNo}%"]];
        ShopUserRebate::mk()->where($map)->update(['status' => 1, 'remark' => '订单已确认收货！']);
        BalanceService::update($orderNo);
        UserUpgrade::upgrade($order['unid'],true);
    }

    /**
     * 取消订单发放返佣
     * @param string $orderNo
     * @return array
     * @throws Exception
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function cancel(string $orderNo): array
    {
        $map = ['status' => 0, 'order_no' => $orderNo];
        $order = ShopOrder::mk()->where($map)->findOrEmpty();
        if ($order->isEmpty()) throw new Exception('订单状态异常');
        // 更新返利记录
        $map = [['deleted', '=', 0], ['order_no', 'like', "{$orderNo}%"]];
        ShopUserRebate::mk()->where($map)->update(['status' => 0, 'deleted' => 1, 'remark' => '订单已取消退回返佣！']);
        return UserUpgrade::upgrade($order['unid']) ? [1, '重新计算返利成功！'] : [0, '重新计算返利失败！'];
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
            $total = ShopUserRebate::mk()->whereRaw("unid='{$unid}' and status=1 and deleted=0")->sum('amount');
            $locks = ShopUserRebate::mk()->whereRaw("unid='{$unid}' and status=0 and deleted=0")->sum('amount');
            [$data['rebate_total'], $data['rebate_used'], $data['rebate_lock']] = [$total, $count, $locks];
            if ($isUpdate && ($user = AccountUser::mk()->findOrEmpty($unid))->isExists()) {
                $user->save(['extra' => array_merge($user->getAttr('extra'), $data)]);
            }
        } else {
            $count = PaymentTransfer::mk()->whereRaw("status>0")->sum('amount');
            $total = ShopUserRebate::mk()->whereRaw("status=1 and deleted=0")->sum('amount');
            $locks = ShopUserRebate::mk()->whereRaw("status=0 and deleted=0")->sum('amount');
            [$data['rebate_total'], $data['rebate_used'], $data['rebate_lock']] = [$total, $count, $locks];
        }
        return [$total, $count, $locks];
    }

    /**
     * 获取配置数据
     * @param ?string $name 配置名称
     * @return array|string
     * @throws Exception
     */
    public static function config(?string $name = null)
    {
        $ckey = 'plugin.shop.rebate.rule';
        $data = sysvar($ckey) ?: sysvar($ckey, sysdata($ckey));
        return is_null($name) ? $data : ($data[$name] ?? '');
    }

    /**
     * 获取等级佣金描述
     * @return array
     * @throws Exception
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function levels(): array
    {
        // 解析等级奖励规则
        $config = [];
        foreach (self::config() as $k => $v) {
            foreach (self::prizes as $t => $n) {
                if (preg_match("#^{$t}_type_vip_(\d+)#", $k, $a) && $v > 0) {
                    $config["{$t}_{$a[1]}"]['type'] = $v;
                    $config["{$t}_{$a[1]}"]['name'] = $n;
                    $config["{$t}_{$a[1]}"]['desc'] = self::pdescs[$t] ?? self::pdescs['_'];
                }
                if (preg_match("#^{$t}_value_vip_(\d+)#", $k, $a) && $v > 0) {
                    $config["{$t}_{$a[1]}"]['value'][] = $v;
                }
            }
        }
        foreach ($config as $k => &$c) {
            if (count($c) === 4 && is_array($c['value'])) {
                $c['value'] = round(max($c['value']));
                if (substr_count($c['desc'], '%s') === 1) {
                    if ($c['type'] == 1) {
                        $c['desc'] = sprintf($c['desc'], " {$c['value']} 元/单");
                    } elseif ($c['type'] == 2) {
                        $c['desc'] = sprintf($c['desc'], "订单金额 {$c['value']}% ");
                    } elseif ($c['type'] == 3) {
                        $c['desc'] = sprintf($c['desc'], "佣金总额 {$c['value']}% ");
                    }
                }
            } else {
                unset($config[$k]);
            }
        }
        // 解析商品折扣规则
        $discs = [];
        foreach (ShopConfigDiscount::items() as $v) {
            foreach ($v['items'] as $vv) $discs[$vv['level']][] = floatval($vv['discount']);
        }
        // 合并等级折扣及奖励
        $levels = ShopConfigLevel::items(null, '*');
        foreach ($levels as &$level) {
            $level['prizes'] = [];
            $disc = round(min($discs[$level['number']] ?? [100]));
            if ($disc < 100) $level['prizes'][] = ['type' => 0, 'value' => $disc, 'name' => '享折扣价', 'desc' => "最高可享受商品的 {$disc}% 折扣价购买~"];
            foreach (UserRebate::prizes as $t => $n) if (isset($config["{$t}_{$level['number']}"])) {
                $level['prizes'][] = $config["{$t}_{$level['number']}"];
            }
        }
        return array_values($levels);
    }

    /**
     * 用户首推奖励
     * @param string $orderNo
     * @return boolean
     * @throws Exception
     */
    private static function first(string $orderNo): bool
    {
        if (empty(self::$rela1)) return false;
        $map = ['order_unid' => self::$unid];
        if (ShopUserRebate::mk()->where($map)->findOrEmpty()->isExists()) return false;
        // 创建返佣奖励记录
        $map = ['type' => self::pfirst, 'order_no' => $orderNo, 'order_unid' => self::$unid];
        $key = sprintf('vip_%d_%d', self::$rela0['level_code'], self::$rela1['level_code']);
        if (self::config("first_type_{$key}") > 0 && ShopUserRebate::mk()->where($map)->findOrEmpty()->isEmpty()) {
            $value = self::config("first_value_{$key}");
            if (self::config("first_type_{$key}") == 1) {
                $val = floatval($value ?: '0.00');
                $name = sprintf('%s，每单 %s 元', self::prizes[self::pfirst], $val);
            } elseif (self::config("first_type_{$key}") == 2) {
                $val = floatval($value * self::$order['rebate_amount'] / 100);
                $name = sprintf('%s，订单金额%s元 的%s%%', self::prizes[self::pfirst],self::$order['rebate_amount'], $value);
            } else {
                $val = floatval($value * self::$order['amount_profit'] / 100);
                $name = sprintf('%s，分佣金额%s元 的%s%%', self::prizes[self::pfirst],self::$order['amount_profit'], $value);
            }
            // 写入返佣记录
            self::wRebate(self::$rela1['unid'], $map, $name, $val);
        }
        return true;
    }


    /**
     * 用户复购奖励
     * @param string $orderNo
     * @return boolean
     * @throws Exception
     */
    protected static function repeat(string $orderNo): bool
    {
        $map = [];
        $map[] = ['order_no', '<>', $orderNo];
        $map[] = ['order_unid', '=', self::$unid];
        if (ShopUserRebate::mk()->where($map)->findOrEmpty()->isExists()) return false;
        // 检查上级可否奖励
        if (empty(self::$rela1)) return false;
        // 创建返佣奖励记录
        $key = sprintf('vip_%d_%d', self::$rela1['level_code'], self::$rela0['level_code']);
        $map = ['type' => self::pRepeat, 'order_no' => $orderNo, 'order_unid' => self::$unid];
        if (self::config("repeat_type_{$key}") > 0 && ShopUserRebate::mk()->where($map)->findOrEmpty()->isEmpty()) {
            $value = self::config("repeat_value_{$key}");
            if (self::config("repeat_type_{$key}") == 1) {
                $val = floatval($value ?: '0.00');
                $name = sprintf("%s，每人 %s 元", self::prizes[self::pRepeat], $val);
            } elseif (self::config("repeat_type_{$key}") == 2) {
                $val = floatval($value * self::$order['rebate_amount'] / 100);
                $name = sprintf("%s，订单金额%s元 的%s%%", self::prizes[self::pRepeat],self::$order['rebate_amount'], $value);
            } else {
                $val = floatval($value * self::$order['amount_profit'] / 100);
                $name = sprintf("%s，分佣金额%s元 的%s%%", self::prizes[self::pRepeat],self::$order['amount_profit'], $value);
            }
            // 写入返佣记录
            self::wRebate(self::$rela1['unid'], $map, $name, $val);
        }
        return true;
    }

    /**
     * 用户直属团队
     * @param string $orderNo
     * @return boolean
     * @throws Exception
     */
    private static function direct(string $orderNo): bool
    {
        if (empty(self::$rela1)) return false;
        $key = self::$rela0['level_code'];
        $map = ['type' => self::pDirect, 'order_no' => $orderNo, 'order_unid' => self::$unid];
        if (self::config("direct_type_vip_{$key}") > 0 && ShopUserRebate::mk()->where($map)->findOrEmpty()->isEmpty()) {
            $value = self::config("direct_value_vip_{$key}");
            if (self::config("direct_type_vip_{$key}") == 1) {
                $val = floatval($value ?: '0.00');
                $name = sprintf("%s，每人 %s 元", self::prizes[self::pDirect], $val);
            } elseif (self::config("direct_type_vip_{$key}") == 2) {
                $val = floatval($value * self::$order['rebate_amount'] / 100);
                $name = sprintf("%s，订单金额%s元 的%s%%", self::prizes[self::pDirect],self::$order['rebate_amount'], $value);
            } else {
                $val = floatval($value * self::$order['amount_profit'] / 100);
                $name = sprintf("%s，分佣金额%s元 的%s%%", self::prizes[self::pDirect],self::$order['amount_profit'], $value);
            }
            // 写入返佣记录
            self::wRebate(self::$rela1['unid'], $map, $name, $val);
        }
        return true;
    }

    /**
     * 用户间接团队
     * @param string $orderNo
     * @return boolean
     * @throws Exception
     */
    private static function indirect(string $orderNo): bool
    {
        if (empty(self::$rela2)) return false;
        $key = self::$rela0['level_code'];
        $map = ['type' => self::pIndirect, 'order_no' => $orderNo, 'order_unid' => self::$unid];
        if (self::config("indirect_type_vip_{$key}") > 0 && ShopUserRebate::mk()->where($map)->findOrEmpty()->isEmpty()) {
            $value = self::config("indirect_value_vip_{$key}");
            if (self::config("indirect_type_vip_{$key}") == 1) {
                $val = floatval($value ?: '0.00');
                $name = sprintf('%s，每人 %s 元', self::prizes[self::pIndirect], $val);
            } elseif (self::config("indirect_type_vip_{$key}") == 2) {
                $val = floatval($value * self::$order['rebate_amount'] / 100);
                $name = sprintf("%s，订单金额%s元 的%s%%", self::prizes[self::pIndirect], self::$order['rebate_amount'],$value);
            } else {
                $val = floatval($value * self::$order['amount_profit'] / 100);
                $name = sprintf("%s，分佣金额%s元 的%s%%", self::prizes[self::pIndirect],self::$order['amount_profit'], $value);
            }
            // 写入返佣记录
            self::wRebate(self::$rela2['unid'], $map, $name, $val);
        }
        return true;
    }

    /**
     * 用户差额奖励
     * @param string $orderNo
     * @return bool
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException|Exception
     */
    private static function margin(string $orderNo): bool
    {
        $puids = array_reverse(str2arr(self::$rela0['path'], ','));
        if (empty($puids) || self::$order['amount_total'] <= 0) return false;
        // 获取可以参与奖励的代理
        $users = AccountRelation::mk()->whereIn('unid', $puids)->orderField('unid', $puids)->select()->toArray();
        if (empty($users)) return true;
        // 查询需要计算奖励的商品
        foreach (ShopOrderItem::mk()->where(['order_no' => $orderNo])->cursor() as $item) {
            if ($item['discount_id'] > 0 && $item['rebate_type'] === 1) {
                [$tVip, $tRate] = [$item['level_code'], $item['discount_rate']];
                $map = ['id' => $item['discount_id'], 'status' => 1, 'deleted' => 0];
                $rules = json_decode(ShopConfigDiscount::mk()->where($map)->value('items', '[]'), true);
                foreach ($users as $user) if (isset($rules[$user['level_code']]) && $user['level_code'] > $tVip) {
                    if (($rule = $rules[$user['level_code']]) && $tRate > $rule['discount']) {
                        $map = ['unid' => $user['unid'], 'type' => self::pMargin, 'order_no' => $orderNo];
                        if (ShopUserRebate::mk()->where($map)->count() < 1) {
                            $vvvv = self::prizes[self::pMargin];
                            $dRate = ($rate = $tRate - $rule['discount']) / 100;
                            $name = "{$vvvv}【{$item['level_name']} => {$user['level_name']}】商品市场价{$item['total_price_market']}元的{$rate}%";
                            $amount = $dRate * $item['total_price_market'];
                            // 写入用户返佣记录
                            self::wRebate($user['unid'], $map, $name, $amount);
                        }
                        [$tVip, $tRate] = [$user['level_code'], $rule['discount']];
                    }
                }
            }
        }
        return true;
    }

    /**
     * 用户管理奖励发放
     * @param string $orderNo
     * @return boolean
     * @throws Exception
     * @throws DbException
     */
    private static function manage(string $orderNo): bool
    {
        $puids = array_reverse(str2arr(self::$rela0['path'], ','));
        if (empty($puids) || self::$order['amount_total'] <= 0) return false;
        // 记录用户原始等级
        $prevLevel = self::$rela0['level_code'];
        $prevName = self::$rela0['level_name'];
        // 获取参与奖励的代理
        foreach (AccountRelation::mk()->whereIn('unid', $puids)->orderField('unid', $puids)->cursor() as $user) {
            if ($user['level_code'] > $prevLevel) {
                if (($amount = self::_prize06amount($prevLevel + 1, $user['level_code'])) > 0.00) {
                    $map = ['unid' => $user['unid'], 'type' => self::pManage, 'order_no' => $orderNo];
                    if (ShopUserRebate::mk()->where($map)->count() < 1) {
                        $name = sprintf("%s 【%s => %s】 每单 %s 元", self::prizes[self::pManage], $prevName, $user['level_name'], $amount);
                        self::wRebate($user['unid'], $map, $name, $amount);
                    }
                }
                $prevLevel = $user['level_code'];
            }
        }
        return true;
    }

    /**
     * 计算两等级之间的管理奖差异
     * @param integer $prevLevel 上个等级
     * @param integer $nextLevel 下个等级
     * @return float
     * @throws Exception
     */
    private static function _prize06amount(int $prevLevel, int $nextLevel): float
    {
        if (self::config("manage_type_vip_{$nextLevel}") == 2) {
            $amount = 0.00;
            foreach (range($prevLevel, $nextLevel) as $level) {
                $value = floatval(self::config("manage_value_vip_{$level}"));
                if (self::config("manage_type_vip_{$level}") > 0 && $value > 0) $amount += $value;
            }
            return $amount;
        } elseif (self::config("manage_type_vip_{$nextLevel}") == 1) {
            return floatval(self::config("manage_value_vip_{$nextLevel}"));
        } else {
            return floatval(0);
        }
    }

    /**
     * 用户升级奖励发放
     * @param string $orderNo
     * @return boolean
     * @throws Exception
     */
    private static function upgrade(string $orderNo): bool
    {
        if (empty(self::$rela1)) return false;
        if (empty(self::$user['extra']['level_order']) || self::$user['extra']['level_order'] !== $orderNo) return false;
        // 创建返佣奖励记录
        $vip = self::$rela0['level_code'];
        $map = ['type' => self::pUpgrade, 'order_no' => $orderNo, 'order_unid' => self::$unid];
        if (self::config("upgrade_type_vip_{$vip}_4") > 0 && ShopUserRebate::mk()->where($map)->findOrEmpty()->isEmpty()) {
            $value = self::config("upgrade_value_vip_{$vip}");
            if (self::config("upgrade_type_vip_{$vip}_4") == 1) {
                $val = floatval($value ?: '0.00');
                $name = sprintf('%s，每人 %s 元', self::prizes[self::pUpgrade], $val);
            } elseif (self::config("upgrade_type_vip_{$vip}_4") == 2) {
                $val = floatval($value * self::$order['rebate_amount'] / 100);
                $name = sprintf("%s，订单金额%s元 的%s%%", self::prizes[self::pUpgrade],self::$order['rebate_amount'], $value);
            } else {
                $val = floatval($value * self::$order['amount_profit'] / 100);
                $name = sprintf("%s，分佣金额%s元 的%s%%", self::prizes[self::pUpgrade],self::$order['amount_profit'], $value);
            }
            // 写入返佣记录
            self::wRebate(self::$rela1['unid'], $map, $name, $val);
        }
        return true;
    }

    /**
     * 用户平推奖励发放
     * @param string $orderNo
     * @return boolean
     */
    private static function equal(string $orderNo): bool
    {
        if (empty(self::$rela1)) return false;
        $map = ['level_code' => self::$rela0['level_code']];
        $unids = array_reverse(str2arr(trim(self::$rela0['path'], ','), ','));
        $puids = AccountRelation::mk()->whereIn('unid', $unids)->orderField('unid', $unids)->where($map)->column('unid');
        if (count($puids) < 2) return false;
        Library::$sapp->db->transaction(function () use ($map, $puids, $orderNo) {
            foreach ($puids as $key => $puid) {
                // 最多两层
                if (($layer = $key + 1) > 2) break;
                // 检查重复
                $map = ['unid' => $puid, 'type' => self::pEqual, 'order_no' => $orderNo];
                if (ShopUserRebate::mk()->where($map)->count() < 1) {
                    // 奖励金额
                    $amount = floatval(self::config("equal_value_vip_{$layer}_" . self::$rela0['level_code']));
                    $name = sprintf("%s, 奖励 %s级代理 %s元", self::prizes[self::pEqual],$key+1, $amount);
                    // 写入返佣
                    self::wRebate($puid, $map, $name, $amount);
                }
            }
        });
        return true;
    }

    /**
     * 获取奖励名称
     * @param string $prize
     * @return string
     */
    public static function name(string $prize): string
    {
        return self::prizes[$prize] ?? $prize;
    }

    /**
     * 获取奖励名称前缀
     * @param string $prize
     * @return string
     */
    public static function prefix(string $prize): string
    {
        return self::prefix[$prize] ?? $prize;
    }

    /**
     * 写入返佣记录
     * @param int $unid 奖励用户
     * @param array $map 查询条件
     * @param string $name 奖励名称
     * @param float $amount 奖励金额
     * @throws Exception
     */
    private static function wRebate(int $unid, array $map, string $name, float $amount)
    {
        ShopUserRebate::mk()->save(array_merge($map, [
            'unid'         => $unid,
            'date'         => date('Y-m-d'),
            'code'         => CodeExtend::uniqidDate(16, 'R'),
            'name'         => $name,
            'amount'       => $amount,
            'status'       => self::$status,
            'order_no'     => self::$order['order_no'],
            'order_unid'   => self::$order['unid'],
            'order_amount' => self::$order['amount_total'],
        ]));
        $code = self::prefix($map['type']).self::$order['order_no'];
        $remark = sprintf("来自分佣订单 %s 金额 %s元 %s", self::$order['order_no'], $amount,$name);
        BalanceService::create($unid, $code, self::name($map['type']), $amount, $remark, false);
    }
}