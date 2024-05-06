<?php

declare (strict_types=1);

namespace plugin\wemall\service;

use plugin\account\model\AccountUser;
use plugin\payment\service\BalanceService;
use plugin\payment\service\IntegralService;
use plugin\shop\service\UserAction;
use plugin\wemall\model\ShopConfigLevel;
use plugin\shop\model\ShopOrder;
use plugin\shop\model\ShopOrderItem;
use plugin\account\model\AccountRelation;
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
abstract class UserUpgrade
{

    /**
     * 读取用户代理编号
     * @param integer $unid 会员用户
     * @param integer $puid 代理用户
     * @param AccountRelation|array|null $relation 关联的模型
     * @return array
     * @throws Exception
     */
    public static function withAgent(int $unid, int $puid, $relation = null): array
    {
        $relation = $relation ?: AccountRelation::sync($unid)->toArray();
        // 绑定代理数据
        $puid1 = $relation['puid1'] ?? 0; // 上1级代理
        $puid2 = $relation['puid2'] ?? 0; // 上2级代理
        $puid3 = $relation['puid3'] ?? 0; // 上3级代理
        if (empty($relation['puids']) && $puid > 0) {
            // 创建临时绑定
            $relation = self::bindAgent($unid, $puid, 0);
            $puid1 = $relation->getAttr('puid1') ?: 0; // 上1级代理
            $puid2 = $relation->getAttr('puid2') ?: 0; // 上2级代理
            $puid3 = $relation->getAttr('puid3') ?: 0; // 上3级代理
        }
        return ['unid' => $unid, 'puid1' => $puid1, 'puid2' => $puid2, 'puid3' => $puid3];
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
            $relation = AccountRelation::sync($unid);
            // 已经绑定不允许替换原代理信息
            $puid1 = intval($relation->getAttr('puid1'));
            if ($puid1 > 0 && $relation->getAttr('puids') > 0) {
                if ($puid1 !== $puid && $mode !== 0) {
                    throw new Exception('已绑定代理！');
                }
            }
            // 检查代理用户
            if (empty($puid)) $puid = $puid1;
            if (empty($puid)) throw new Exception('代理不存在！');
            if ($unid === $puid) throw new Exception('不能绑定自己！');
            // 检查上级用户
            $parent = AccountRelation::sync($puid);
            if (strpos($parent->getAttr('path'), ",{$unid},") !== false) {
                throw new Exception('不能绑定下级！');
            }
            Library::$sapp->db->transaction(function () use ($relation, $parent, $mode) {
                self::forceReplaceParent($relation, $parent, ['puids' => $mode > 0 ? 1 : 0]);
            });
            return static::upgrade($relation->getAttr('unid'));
        } catch (Exception $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            throw new Exception("绑定代理失败, {$exception->getMessage()}");
        }
    }

    /**
     * 更替用户上级关系
     * @param AccountRelation $relation
     * @param AccountRelation $parent
     * @param array $extra 扩展数据
     * @return AccountRelation
     */
    public static function forceReplaceParent(AccountRelation $relation, AccountRelation $parent, array $extra = []): AccountRelation
    {
        $path1 = arr2str(str2arr("{$parent->getAttr('path')},{$parent->getAttr('unid')}"));
        $relation->save(array_merge([
            'path'  => $path1,
            'puid1' => $parent->getAttr('unid'),
            'puid2' => $parent->getAttr('puid1'),
            'puid3' => $parent->getAttr('puid2'),
            'layer' => substr_count($path1, ','),
        ], $extra));
        /** 更新所有下级代理 @var AccountRelation $item */
        $path2 = arr2str(str2arr("{$relation->getAttr('path')},{$relation->getAttr('unid')}"));
        foreach (AccountRelation::mk()->whereLike('path', "{$path2}%")->order('layer desc')->cursor() as $item) {
            $text = arr2str(str2arr("{$relation->getAttr('path')},{$relation->getAttr('unid')}"));
            $attr = array_reverse(str2arr($path3 = preg_replace("#^{$path2}#", $text, $item->getAttr('path'))));
            $item->save([
                'puid1' => $attr[0] ?? 0, 'puid2' => $attr[1] ?? 0, 'path' => $path3,
                'puid3' => $attr[2] ?? 0, 'layer' => substr_count($path3, ',')
            ]);
        }
        return $relation;
    }

    /**
     * 同步计算用户等级
     * @param integer $unid 指定用户UID
     * @param boolean $parent 同步计算上级
     * @param ?string $orderNo 升级触发订单
     * @return AccountRelation
     * @throws Exception
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function upgrade(int $unid, bool $parent = true, ?string $orderNo = null): AccountRelation
    {
        $relation = AccountRelation::sync($unid);
        $levelCurr = intval($relation->getAttr('level_code'));
        // 筛选用户等级
        $levels = ShopConfigLevel::mk()->where(['status' => 1])->select()->toArray();
        [$levelName, $levelCode, $levelTeams] = [$levels[0]['name'] ?? '普通用户', 0, []];
        // 统计用户数据
        foreach ($levels as $level => $vo) if ($vo['upgrade_team'] === 1) $levelTeams[] = $level;
        // 统计团队数据
        $model = AccountRelation::mk()->where(['level_code' => $levelTeams]);
        $teamsTotal = (clone $model)->whereLike('path', "{$relation->getAttr('path')}%")->count();
        $teamsDirect = (clone $model)->where(['puid1' => $unid])->count();
        $teamsIndirect = (clone $model)->where(['puid2' => $unid])->count();
        // 统计订单金额
        $orderAmount = ShopOrder::mk()->where("unid={$unid} and status>=4")->sum('amount_total');
        // 动态计算用户等级
        foreach (array_reverse($levels) as $item) {
            $l1 = empty($item['enter_vip_status']) || $relation->getAttr('buy_vip_entry') > 0;
            $l2 = empty($item['order_amount_status']) || $item['order_amount_number'] <= $orderAmount;
            $l3 = empty($item['teams_users_status']) || $item['teams_users_number'] <= $teamsTotal;
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

        // 收集用户团队数据
        $extra = [
            'teams_users_total'    => $teamsTotal,
            'teams_users_direct'   => $teamsDirect,
            'teams_users_indirect' => $teamsIndirect,
            'order_amount_total'   => $orderAmount,
        ];
        if (!empty($orderNo)) $extra['level_order'] = $orderNo;
        if ($levelCode !== $levelCurr) $extra['level_change_time'] = date('Y-m-d H:i:s');
        // 更新用户扩展数据
        $user = AccountUser::mk()->findOrEmpty($unid);
        $user->save(['extra' => array_merge($user->getAttr('extra'), $extra)]);
        // 用户等级数据
        $relation->save(['level_name' => $levelName, 'level_code' => $levelCode]);
        $levelCurr < $levelCode && Library::$sapp->event->trigger('PluginWemallUpgradeLevel', [
            'unid'           => $unid,
            'order_no'       => $orderNo,
            'level_code_old' => $levelCurr,
            'level_code_new' => $levelCode,
        ]);
        if ($parent && empty($relation->getAttr('puids')) && $relation->getAttr('puid1') > 0) {
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
            AccountRelation::sync($unid);
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