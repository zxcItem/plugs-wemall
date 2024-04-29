<?php


declare (strict_types=1);

namespace plugin\wemall\controller\base;

use plugin\wemall\model\ShopConfigLevel;
use plugin\wemall\service\UserRebate;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 用户等级管理
 * @class Level
 * @package plugin\wemall\controller\base
 */
class Level extends Controller
{
    /**
     * 用户等级管理
     * @auth true
     * @menu true
     * @return void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        ShopConfigLevel::mQuery()->layTable(function () {
            $this->title = '用户等级管理';
        }, static function (QueryHelper $query) {
            $query->like('name')->equal('status')->dateBetween('create_time');
        });
    }

    /**
     * 添加用户等级
     * @auth true
     * @return void
     * @throws DbException
     */
    public function add()
    {
        $this->max = ShopConfigLevel::maxNumber() + 1;
        ShopConfigLevel::mForm('form');
    }

    /**
     * 编辑用户等级
     * @auth true
     * @return void
     * @throws DbException
     */
    public function edit()
    {
        $this->max = ShopConfigLevel::maxNumber();
        ShopConfigLevel::mForm('form');
    }

    /**
     * 表单数据处理
     * @param array $vo
     * @throws DbException
     */
    protected function _form_filter(array &$vo)
    {
        if ($this->request->isGet()) {
            $this->prizes = UserRebate::prizes;
            $vo['number'] = $vo['number'] ?? ShopConfigLevel::maxNumber();
        } else {
            $vo['utime'] = time();
            // 用户升级条件开关
            $vo['enter_vip_status'] = isset($vo['enter_vip_status']) ? 1 : 0;
            $vo['teams_users_status'] = isset($vo['teams_users_status']) ? 1 : 0;
            $vo['teams_direct_status'] = isset($vo['teams_direct_status']) ? 1 : 0;
            $vo['teams_indirect_status'] = isset($vo['teams_indirect_status']) ? 1 : 0;
            $vo['order_amount_status'] = isset($vo['order_amount_status']) ? 1 : 0;
            // 根据数量判断状态
            $vo['teams_users_status'] = intval($vo['teams_users_status'] && $vo['teams_users_number'] > 0);
            $vo['teams_direct_status'] = intval($vo['teams_direct_status'] && $vo['teams_direct_number'] > 0);
            $vo['teams_indirect_status'] = intval($vo['teams_indirect_status'] && $vo['teams_indirect_number'] > 0);
            $vo['order_amount_status'] = intval($vo['order_amount_status'] && $vo['order_amount_number'] > 0);
            // 检查升级条件配置
            $count = 0;
            foreach ($vo as $k => $v) if (is_numeric(stripos($k, '_status'))) $count += $v;
            if (empty($count) && $vo['number'] > 0) $this->error('升级条件不能为空！');
        }
    }

    /**
     * 表单结果处理
     * @param boolean $state
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function _form_result(bool $state)
    {
        if ($state) {
            $isasc = input('old_number', 0) <= input('number', 0);
            $order = $isasc ? 'number asc,utime asc' : 'number asc,utime desc';
            foreach (ShopConfigLevel::mk()->order($order)->select() as $number => $upgrade) {
                $upgrade->save(['number' => $number]);
            }
        }
    }

    /**
     * 修改等级状态
     * @auth true
     */
    public function state()
    {
        ShopConfigLevel::mSave();
    }

    /**
     * 删除用户等级
     * @auth true
     */
    public function remove()
    {
        ShopConfigLevel::mDelete();
    }

    /**
     * 状态变更处理
     * @auth true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    protected function _save_result()
    {
        $this->_form_result(true);
    }

    /**
     * 删除结果处理
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    protected function _delete_result()
    {
        $this->_form_result(true);
    }
}