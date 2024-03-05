<?php


declare (strict_types=1);

namespace plugin\wemall\controller\user;

use plugin\account\model\AccountUser;
use plugin\wemall\model\ShopConfigLevel;
use plugin\wemall\model\ShopUserRebate;
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
        ShopUserRebate::mQuery()->layTable(function () {
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

    /**
     * 用户返佣配置
     * @auth true
     * @throws Exception
     */
    public function config()
    {
        $this->skey = 'plugin.shop.rebate.rule';
        $this->title = '用户返佣配置';
        if ($this->request->isGet()) {
            $this->data = sysdata($this->skey);
            $this->levels = ShopConfigLevel::items();
            $this->fetch();
        } else {
            sysdata($this->skey, $this->request->post());
            $this->success('奖励修改成功', 'javascript:history.back()');
        }
    }

    /**
     * 刷新订单返佣
     * @auth true
     * @return void
     */
//    public function sync()
//    {
//        $this->_queue('刷新用户返佣数据', 'xdata:mall:rebate');
//    }
}