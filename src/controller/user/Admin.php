<?php


declare (strict_types=1);

namespace plugin\wemall\controller\user;

use plugin\account\model\AccountUser;
use plugin\wemall\model\ShopConfigLevel;
use plugin\wemall\model\ShopUserRelation;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 用户关系管理
 * @class Admin
 * @package plugin\wemall\controller\user
 */
class Admin extends Controller
{
    /**
     * 用户关系管理
     * @auth true
     * @menu true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        $this->type = $this->get['type'] ?? 'index';
        ShopUserRelation::mQuery()->layTable(function () {
            $this->title = '用户关系管理';
            $this->upgrades = ShopConfigLevel::items();
        }, function (QueryHelper $query) {
            $query->with(['user', 'relation0', 'relation1', 'relation2'])->equal('level_code');
            // 用户内容查询
            $user = AccountUser::mQuery()->dateBetween('create_at');
            $user->equal('status')->like('code|phone|username|nickname#user');
            $user->where(['status' => intval($this->type === 'index'), 'deleted' => 0]);
            $query->whereRaw("unid in {$user->db()->field('id')->buildSql()}");
        });
    }

    /**
     * 刷新会员数据
     * @auth true
     * @return void
     */
    public function sync()
    {
        $this->_queue('刷新会员用户数据', 'xdata:mall:users');
    }

    /**
     * 修改用户状态
     * @auth true
     */
    public function state()
    {
        AccountUser::mSave($this->_vali([
            'status.in:0,1'  => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除用户账号
     * @auth true
     */
    public function remove()
    {
        AccountUser::mDelete();
    }

    /**
     * 修改用户上级
     * @auth true
     * @return void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function parent()
    {
        $this->index();
    }
}