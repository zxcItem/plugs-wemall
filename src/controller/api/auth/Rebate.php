<?php


declare (strict_types=1);

namespace plugin\wemall\controller\api\auth;

use plugin\wemall\controller\api\Auth;
use plugin\wemall\model\ShopConfigLevel;
use plugin\wemall\model\ShopUserRebate;
use plugin\wemall\service\UserRebate;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 用户返佣管理
 * @class Rebate
 * @package plugin\wemall\controller\api\auth
 */
class Rebate extends Auth
{
    /**
     * 获取用户返佣记录
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function get()
    {
        $date = trim(input('date', date('Y-m')), '-');
        [$map, $year] = [['unid' => $this->unid], substr($date, 0, 4)];
        $query = ShopUserRebate::mQuery()->where($map)->equal('type,status')->whereLike('date', "{$date}%");
        $this->success('获取返佣统计', array_merge($query->order('id desc')->page(true, false, false, 10), [
            'total' => [
                '年度' => ShopUserRebate::mQuery()->where($map)->equal('type,status')->whereLike('date', "{$year}%")->db()->sum('amount'),
                '月度' => ShopUserRebate::mQuery()->where($map)->equal('type,status')->whereLike('date', "{$date}%")->db()->sum('amount'),
            ],
        ]));
    }

    /**
     * 获取我的奖励
     */
    public function prize()
    {
        [$map, $data] = [['number' => $this->levelCode], []];
        $prizes = ShopUserRebate::mk()->group('name')->column('name');
        $rebate = ShopConfigLevel::mk()->where($map)->value('rebate_rule', '');
        $codemap = array_merge($prizes, str2arr($rebate));
        foreach (UserRebate::prizes as $code => $prize) {
            if (in_array($code, $codemap)) $data[$code] = $prize;
        }
        $this->success('获取我的奖励', $data);
    }

    /**
     * 获取奖励配置
     */
    public function prizes()
    {
        $this->success('获取系统奖励', array_values(UserRebate::prizes));
    }
}