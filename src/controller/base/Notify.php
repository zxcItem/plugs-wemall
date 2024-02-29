<?php


declare (strict_types=1);

namespace plugin\wemall\controller\base;

use plugin\wemall\model\ShopConfigLevel;
use plugin\wemall\model\ShopConfigNotify;
use think\admin\Controller;
use think\admin\extend\CodeExtend;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 系统通知管理
 * @class Notify
 * @package plugin\wemall\controller\base
 */
class Notify extends Controller
{
    /**
     * 系统通知管理
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
        ShopConfigNotify::mQuery()->layTable(function () {
            $this->title = '系统通知管理';
        }, function (QueryHelper $query) {
            $query->like('name,code')->equal('status')->dateBetween('create_time');
            $query->where(['deleted' => 0, 'status' => intval($this->type === 'index')]);
        });
    }

    /**
     * 添加系统通知
     * @auth true
     */
    public function add()
    {
        $this->title = '添加系统通知';
        ShopConfigNotify::mForm('form');
    }

    /**
     * 编辑系统通知
     * @auth true
     */
    public function edit()
    {
        $this->title = '编辑系统通知';
        ShopConfigNotify::mForm('form');
    }

    /**
     * 表单数据处理
     * @return void
     */
    protected function _form_filter(array &$data)
    {
        if (empty($data['code'])) {
            $data['code'] = CodeExtend::uniqidNumber(16, 'N');
        }
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
            $this->success('通知保存成功！', 'javascript:history.back()');
        } else {
            $this->error('通知保存失败！');
        }
    }

    /**
     * 修改通知状态
     * @auth true
     */
    public function state()
    {
        ShopConfigNotify::mSave($this->_vali([
            'status.in:0,1'  => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除系统通知
     * @auth true
     */
    public function remove()
    {
        ShopConfigNotify::mDelete();
    }
}