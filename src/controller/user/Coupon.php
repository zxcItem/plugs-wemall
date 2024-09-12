<?php


declare (strict_types=1);

namespace plugin\wemall\controller\user;

use plugin\account\model\PluginAccountUser;
use plugin\wemall\model\PluginWemallUserCoupon;
use think\admin\Controller;
use think\admin\helper\QueryHelper;

/**
 * 用户卡券管理
 * @class Coupon
 * @package plugin\wemall\controller\user
 */
class Coupon extends Controller
{
    /**
     * 用户卡券管理
     * @auth true
     * @menu true
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        PluginWemallUserCoupon::mQuery()->layTable(function () {
            $this->title = '用户卡券管理';
        }, function (QueryHelper $query) {
            // 数据关联
            $query->with(['coupon', 'bindUser']);
            // 代理条件查询
            $query->like('code')->dateBetween('create_time');
            // 会员条件查询
            $db = PluginAccountUser::mQuery()->like('nickname|phone#user')->db();
            if ($db->getOptions('where')) $query->whereRaw("order_unid in {$db->field('id')->buildSql()}");
        });
    }
}