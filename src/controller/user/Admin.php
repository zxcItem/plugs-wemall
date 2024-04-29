<?php


declare (strict_types=1);

namespace plugin\wemall\controller\user;

use plugin\account\model\AccountUser;
use plugin\account\service\Account;
use plugin\wemall\service\UserUpgrade;
use plugin\wemall\model\ShopConfigLevel;
use plugin\account\model\AccountRelation;
use think\admin\Controller;
use think\admin\Exception;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\HttpResponseException;

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
        AccountRelation::mQuery()->layTable(function () {
            $this->title = '用户关系管理';
            $this->upgrades = ShopConfigLevel::items();
        }, function (QueryHelper $query) {
            if (!empty($this->get['unid'])) {
                $query->where('unid', '<>', $this->get['unid']);
                $query->whereNotLike("path", "%,{$this->get['unid']},%");
            }
            $query->with(['user', 'agent1', 'agent2', 'user1', 'user2'])->equal('level_code');
            // 用户内容查询
            $user = AccountUser::mQuery()->dateBetween('create_time');
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
        $this->_queue('刷新会员用户数据', 'plugin:mall:users');
    }

    /**
     * 编辑会员资料
     * @auth true
     * @return void
     */
    public function edit()
    {
        AccountRelation::mQuery()->with('user')->mForm('form', 'unid');
    }

    /**
     * 表单数据处理
     * @param array $data
     * @return void
     * @throws Exception
     */
    protected function _edit_form_filter(array $data)
    {
        if ($this->request->isPost()) {
            $account = Account::mk(Account::WEB, ['unid' => $data['unid']]);
            // 更新当前用户代理线，同时更新账号的 user 数据
            $account->bind(['id' => $data['unid']], $data['user'] ?? []);
            // 修改用户登录密码
            if (!empty($data['user']['password'])) {
                $account->pwdModify($data['user']['password']);
                unset($data['user']['password']);
            }
        }
    }

    /**
     * 修改用户状态
     * @auth true
     */
    public function state()
    {
        AccountRelation::mSave($this->_vali([
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
        AccountRelation::mDelete();
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
        if ($this->request->isGet()) {
            $this->index();
        } else try {
            $data = $this->_vali(['unid.require' => '用户编号为空！', 'puid.require' => '上级编号为空！']);
            $parent = AccountRelation::mQuery()->where(['unid' => $data['puid']])->findOrEmpty();
            if ($parent->isEmpty()) $this->error('上级用户不存在！');
            $relation = AccountRelation::sync(intval($data['unid']));
            if (stripos($parent->getAttr('path'), ",{$data['unid']},") !== false) {
                $this->error('无法设置下级为自己的上级！');
            }
            $this->app->db->transaction(function () use ($relation, $parent) {
                UserUpgrade::forceReplaceParent($relation, $parent);
            });
            $this->success('更新上级成功！');
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}