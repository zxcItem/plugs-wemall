<?php


declare (strict_types=1);

namespace plugin\wemall\service;

use plugin\account\model\AccountUser;
use plugin\wemall\model\ShopOrderCart;
use plugin\wemall\model\ShopActionCollect;
use plugin\wemall\model\ShopActionHistory;
use think\db\exception\DbException;

/**
 * 用户行为数据服务
 * @class UserAction
 * @package plugin\wemall\service
 */
class UserAction
{
    /**
     * 设置行为数据
     * @param integer $unid 用户编号
     * @param string $gcode 商品编号
     * @param string $type 行为类型
     * @return array
     * @throws DbException
     */
    public static function set(int $unid, string $gcode, string $type): array
    {
        $data = ['unid' => $unid, 'gcode' => $gcode];
        if ($type === 'collect') {
            $model = ShopActionCollect::mk()->where($data)->findOrEmpty();
        } else {
            $model = ShopActionHistory::mk()->where($data)->findOrEmpty();
        }
        $data['sort'] = time();
        $data['times'] = $model->isExists() ? $model->getAttr('times') + 1 : 1;
        $model->save($data) && UserAction::recount($unid);
        return $model->toArray();
    }

    /**
     * 删除行为数据
     * @param integer $unid 用户编号
     * @param string $gcode 商品编号
     * @param string $type 行为类型
     * @return array
     * @throws DbException
     */
    public static function del(int $unid, string $gcode, string $type): array
    {
        $data = [['unid', '=', $unid], ['gcode', 'in', str2arr($gcode)]];
        if ($type === 'collect') {
            ShopActionCollect::mk()->where($data)->delete();
        } else {
            ShopActionHistory::mk()->where($data)->delete();
        }
        self::recount($unid);
        return $data;
    }

    /**
     * 清空行为数据
     * @param integer $unid 用户编号
     * @param string $type 行为类型
     * @return array
     * @throws DbException
     */
    public static function clear(int $unid, string $type): array
    {
        $data = [['unid', '=', $unid]];
        if ($type === 'collect') {
            ShopActionCollect::mk()->where($data)->delete();
        } else {
            ShopActionHistory::mk()->where($data)->delete();
        }
        self::recount($unid);
        return $data;
    }

    /**
     * 刷新用户行为统计
     * @param integer $unid 用户编号
     * @param array|null $data 非数组时更新数据
     * @return array [collect, history, mycarts]
     * @throws DbException
     */
    public static function recount(int $unid, ?array &$data = null): array
    {
        $isUpdate = !is_array($data);
        if ($isUpdate) $data = [];
        // 更新收藏及足迹数量和购物车
        $map = ['unid' => $unid];
        $data['mycarts_total'] = ShopOrderCart::mk()->where($map)->sum('number');
        $data['collect_total'] = ShopActionCollect::mk()->where($map)->count();
        $data['history_total'] = ShopActionHistory::mk()->where($map)->count();
        if ($isUpdate && ($user = AccountUser::mk()->findOrEmpty($unid))->isExists()) {
            $user->save(['extra' => array_merge($user->getAttr('extra'), $data)]);
        }
        return [$data['collect_total'], $data['history_total'], $data['mycarts_total']];
    }
}