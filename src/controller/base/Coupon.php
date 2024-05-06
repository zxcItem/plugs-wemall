<?php


declare (strict_types=1);

namespace plugin\wemall\controller\base;

use plugin\wemall\model\ShopConfigCoupon;
use plugin\wemall\model\ShopConfigLevel;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 优惠券管理
 * @class Coupon
 * @package plugin\wemall\controller\base
 */
class Coupon extends Controller
{
    /**
     * 优惠券管理
     * @auth true
     * @menu true
     * @return void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        $this->type = $this->get['type'] ?? 'index';
        ShopConfigCoupon::mQuery()->layTable(function () {
            $this->title = '优惠券管理';
        }, function (QueryHelper $query) {
            $query->like('name')->equal('status')->dateBetween('create_time');
            $query->where(['deleted' => 0, 'status' => intval($this->type === 'index')]);
        });
    }

    /**
     * 添加优惠券
     * @auth true
     */
    public function add()
    {
        $this->title = '添加优惠券';
        ShopConfigCoupon::mForm('form');
    }

    /**
     * 编辑优惠券
     * @auth true
     */
    public function edit()
    {
        $this->title = '编辑优惠券';
        ShopConfigCoupon::mForm('form');
    }

    /**
     * 表单数据处理
     * @return void
     */
    protected function _form_filter(array &$data)
    {
        if ($this->request->isGet()) {
            $this->levels = ShopConfigLevel::items();
            array_unshift($this->levels, ['name' => '全部', 'number' => '-']);
        } else {
            $data['levels'] = arr2str($data['levels'] ?? []);
        }
    }

    /**
     * 表单结果处理
     * @param bool $result
     * @return void
     */
    protected function _form_result(bool $result)
    {
        if ($result) {
            $this->success('优惠券保存成功！', 'javascript:history.back()');
        } else {
            $this->error('优惠券保存失败！');
        }
    }

    /**
     * 修改优惠券
     * @auth true
     */
    public function state()
    {
        ShopConfigCoupon::mSave($this->_vali([
            'status.in:0,1'  => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除优惠券
     * @auth true
     */
    public function remove()
    {
        ShopConfigCoupon::mDelete();
    }
}