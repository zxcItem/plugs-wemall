<?php

declare (strict_types=1);

namespace plugin\wemall\service;

use plugin\account\model\AccountRelation;
use plugin\account\model\AccountUser;
use plugin\payment\model\PaymentBalance;
use plugin\payment\service\BalanceService;
use plugin\payment\service\IntegralService;
use plugin\shop\model\ShopOrder;
use plugin\shop\model\ShopOrderItem;
use plugin\shop\service\UserAction;
use plugin\wemall\model\ShopConfigLevel;
use think\admin\Exception;
use think\admin\Library;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 用户等级升级服务
 * @class UserUpgrade
 * @package plugin\wemall\service
 */
class UserUpgrade
{

    /**
     * 读取用户代理编号
     * @param integer $unid 会员用户
     * @param integer $puid 代理用户
     * @param array|null $relation
     * @return array
     * @throws Exception
     */
    public static function withAgent(int $unid, int $puid, $relation = null): array
    {
        $relation = $relation ?: AccountRelation::make($unid)->toArray();
        // 绑定代理数据
        $puid1 = $relation['puid1'] ?? 0; // 上1级代理
        $puid2 = $relation['puid2'] ?? 0; // 上2级代理
        if (empty($relation['puid0']) && $puid > 0) {
            $relation = self::bindAgent($unid, $puid, 0);
            $puid1 = $relation->getAttr('puid1') ?: 0; // 上1级代理
            $puid2 = $relation->getAttr('puid2') ?: 0; // 上2级代理
        }
        return ['unid' => $unid, 'puid1' => $puid1, 'puid2' => $puid2];
    }

    /**
     * 尝试绑定上级代理
     * @param integer $unid 用户 UNID
     * @param integer $puid 代理 UNID
     * @param integer $mode 操作类型（0临时绑定, 1永久绑定, 2强行绑定）
     * @return AccountRelation
     * @throws Exception
     */
    public static function bindAgent(int $unid, int $puid = 0, int $mode = 1): AccountRelation
    {
        try {
            $relation = AccountRelation::make($unid);
            // 已经绑定代理
            $puid1 = intval($relation->getAttr('puid1'));
            if ($puid1 > 0 && $relation->getAttr('puid0') > 0) {
                if ($puid1 !== $puid && $mode !== 0) throw new Exception('已绑定代理！');
            }
            // 检查代理用户
            if (empty($puid)) $puid = $puid1;
            if (empty($puid)) throw new Exception('代理不存在！');
            if ($unid === $puid) throw new Exception('不能绑定自己！');
            // 检查上级用户
            $parent = AccountRelation::make($puid);
            if (strpos($parent->getAttr('path'), ",{$unid},") !== false) throw new Exception('不能绑定下级');
            Library::$sapp->db->transaction(static function () use ($relation, $parent, $mode) {
                // 更新用户代理
                $path1 = rtrim($parent->getAttr('path') ?: ',', ',') . ",{$parent->getAttr('unid')},";
                $relation->save([
                    'pids'  => $mode > 0 ? 1 : 0,
                    'path'  => $path1,
                    'puid0' => $mode > 0 ? 1 : 0,
                    'puid1' => $parent->getAttr('unid'),
                    'puid2' => $parent->getAttr('puid1'),
                    'layer' => substr_count($path1, ',')
                ]);
                // 更新下级代理
                $path2 = arr2str(str2arr("{$relation->getAttr('path')},{$relation->getAttr('unid')}"));
                foreach (AccountRelation::mk()->whereLike('path', "{$path2}%")->order('layer desc')->cursor() as $item) {
                    $text = arr2str(str2arr("{$path1},{$relation->getAttr('unid')}"));
                    $attr = array_reverse(str2arr($path3 = preg_replace("#^{$path2}#", $text, $item->getAttr('path'))));
                    $item->save(['path' => $path3, 'puid1' => $attr[0] ?? 0, 'puid2' => $attr[1] ?? 0]);
                }
            });
            return static::upgrade($relation->getAttr('unid'));
        } catch (Exception $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            throw new Exception("绑定代理失败, {$exception->getMessage()}");
        }
    }

    /**
     * 同步计算用户等级
     * @param integer $unid 指定用户UID
     * @param boolean $parent 同步计算上级
     * @param ?string $orderNo 升级触发订单
     * @return AccountRelation
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException|Exception
     */
    public static function upgrade(int $unid, bool $parent = true, ?string $orderNo = null): AccountRelation
    {
        $relation = AccountRelation::make($unid);
        $levelCurr = intval($relation->getAttr('level_code'));
        // 初始化等级参数
        $levels = ShopConfigLevel::mk()->where(['status' => 1])->select()->toArray();
        [$levelName, $levelCode, $levelTeams] = [$levels[0]['name'] ?? '普通用户', 0, []];
        // 统计用户数据
        foreach ($levels as $level => $vo) if ($vo['upgrade_team'] === 1) $levelTeams[] = $level;
        $orderAmount = ShopOrder::mk()->where("unid={$unid} and status>=4")->sum('amount_total');
        $teamsDirect = AccountRelation::mk()->where(['puid1' => $unid])->whereIn('level_code', $levelTeams)->count();
        $teamsIndirect = AccountRelation::mk()->where(['puid2' => $unid])->whereIn('level_code', $levelTeams)->count();
        $teamsUsers = $teamsDirect + $teamsIndirect;
        // 动态计算用户等级
        foreach (array_reverse($levels) as $item) {
            $l1 = empty($item['enter_vip_status']) || $relation->getAttr('buy_vip_entry') > 0;
            $l2 = empty($item['teams_users_status']) || $item['teams_users_number'] <= $teamsUsers;
            $l3 = empty($item['order_amount_status']) || $item['order_amount_number'] <= $orderAmount;
            $l4 = empty($item['teams_direct_status']) || $item['teams_direct_number'] <= $teamsDirect;
            $l5 = empty($item['teams_indirect_status']) || $item['teams_indirect_number'] <= $teamsIndirect;
            if (
                ($item['upgrade_type'] == 0 && ($l1 || $l2 || $l3 || $l4 || $l5)) /* 满足任何条件 */
                ||
                ($item['upgrade_type'] == 1 && ($l1 && $l2 && $l3 && $l4 && $l5)) /* 满足所有条件 */
            ) {
                [$levelName, $levelCode] = [$item['name'], $item['number']];
                break;
            }
        }
        // 购买入会商品升级
        $query = ShopOrderItem::mk()->alias('b')->join([ShopOrder::mk()->getTable() => 'a'], 'b.order_no=a.order_no');
        $tmpCode = $query->whereRaw("a.unid={$unid} and a.payment_status=1 and a.status>=4 and b.level_upgrade>-1")->max('b.level_upgrade');
        if ($tmpCode > $levelCode && isset($levels[$tmpCode])) {
            [$levelName, $levelCode] = [$levels[$tmpCode]['name'], $levels[$tmpCode]['number']];
        } else {
            $orderNo = null;
        }
        // 统计用户订单金额
        $orderAmountTotal = ShopOrder::mk()->whereRaw("unid={$unid} and status>=4")->sum('amount_goods');
        $teamsAmountDirect = ShopOrder::mk()->whereRaw("puid1={$unid} and status>=4")->sum('amount_goods');
        $teamsAmountIndirect = ShopOrder::mk()->whereRaw("puid2={$unid} and status>=4")->sum('amount_goods');
        // 收集用户团队数据
        $extra = [
            'teams_users_total'     => $teamsUsers,
            'teams_users_direct'    => $teamsDirect,
            'teams_users_indirect'  => $teamsIndirect,
            'teams_amount_total'    => $teamsAmountDirect + $teamsAmountIndirect,
            'teams_amount_direct'   => $teamsAmountDirect,
            'teams_amount_indirect' => $teamsAmountIndirect,
            'order_amount_total'    => $orderAmountTotal,
        ];
        if (!empty($orderNo)) $extra['level_order'] = $orderNo;
        if ($levelCode !== $levelCurr) $extra['level_change_time'] = date('Y-m-d H:i:s');
        // 更新用户扩展数据
        $user = AccountUser::mk()->findOrEmpty($unid);
        $user->save(['extra' => array_merge($user->getAttr('extra'), $extra)]);
        // 用户用户等级数据
        $relation->save(['level_name' => $levelName, 'level_code' => $levelCode]);
        $levelCurr < $levelCode && Library::$sapp->event->trigger('PluginWemallUpgradeLevel', [
            'unid'           => $unid,
            'order_no'       => $orderNo,
            'level_code_old' => $levelCurr,
            'level_code_new' => $levelCode,
        ]);
        if ($parent && empty($relation->getAttr('puid0')) && $relation->getAttr('puid1') > 0) {
            static::upgrade(intval($relation->getAttr('puid1')));
        }
        return $relation;
    }

    /**
     * 同步重算用户数据
     * @param integer $unid 指定用户UID
     * @param boolean $syncRelation 同步更新状态
     * @return void
     * @throws Exception
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function recount(int $unid, bool $syncRelation = false)
    {
        if ($syncRelation) {
            AccountRelation::make($unid);
            static::upgrade($unid);
        }
        $data = [];
        // 重算用户余额 & 重算积分
        BalanceService::recount($unid, $data);
        IntegralService::recount($unid, $data);
        // 重算行为统计 & 订单返佣
        UserAction::recount($unid, $data);
        UserRebate::recount($unid, $data);
        if (($user = AccountUser::mk()->findOrEmpty($unid))->isExists()) {
            $user->save(['extra' => array_merge($user->getAttr('extra'), $data)]);
        }
    }
}