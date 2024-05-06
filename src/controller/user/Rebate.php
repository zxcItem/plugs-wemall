<?php


declare (strict_types=1);

namespace plugin\wemall\controller\user;

use plugin\account\model\AccountUser;
use plugin\wemall\model\ShopConfigLevel;
use plugin\wemall\model\ShopRebate;
use plugin\wemall\service\UserRebate;
use think\admin\Controller;
use think\admin\Exception;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\db\Query;

/**
 * 用户返佣管理
 * @class Rebate
 * @package plugin\wemall\controller\user
 */
class Rebate extends Controller
{
    /**
     * 用户返佣管理
     * @auth true
     * @menu true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        ShopRebate::mQuery()->layTable(function () {
            $this->title = '用户返佣管理';
            $this->rebate = UserRebate::recount(0);
        }, static function (QueryHelper $query) {
            // 数据关联
            $query->equal('type')->like('name,order_no')->dateBetween('create_time')->with([
                'user'  => function (Query $query) {
                    $query->field('id,code,phone,nickname,headimg');
                },
                'ouser' => function (Query $query) {
                    $query->field('id,code,phone,nickname,headimg');
                }
            ]);

            // 会员条件查询
            $db = AccountUser::mQuery()->like('nickname|phone#user')->db();
            if ($db->getOptions('where')) $query->whereRaw("order_unid in {$db->field('id')->buildSql()}");

            // 代理条件查询
            $db = AccountUser::mQuery()->like('nickname|phone#agent')->db();
            if ($db->getOptions('where')) $query->whereRaw("unid in {$db->field('id')->buildSql()}");
        });
    }

    /**
     * @param array $data
     * @return void
     */
    protected function _index_page_filter(array &$data)
    {
        foreach ($data as &$vo) $vo['status'] = $vo['status'] ? '已生效' : '未生效';
    }
}