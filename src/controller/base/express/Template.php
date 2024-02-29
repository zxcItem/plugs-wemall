<?php

declare (strict_types=1);

namespace plugin\wemall\controller\base\express;

use plugin\wemall\model\ShopExpressCompany;
use plugin\wemall\model\ShopExpressTemplate;
use plugin\wemall\service\ExpressService;
use think\admin\Controller;
use think\admin\Exception;
use think\admin\extend\CodeExtend;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 邮费模板管理
 * @class Template
 * @package plugin\wemall\controller\base\express
 */
class Template extends Controller
{
    /**
     * 快递邮费模板
     * @auth true
     * @menu true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        $this->type = $this->get['type'] ?? 'index';;
        ShopExpressTemplate::mQuery()->layTable(function () {
            $this->title = '快递邮费模板';
        }, function (QueryHelper $query) {
            $query->like('code,name')->equal('status')->dateBetween('create_at');
            $query->where(['deleted' => 0, 'status' => intval($this->type === 'index')]);
        });
    }

    /**
     * 配送区域管理
     * @auth true
     * @return void
     * @throws Exception
     */
    public function region()
    {
        if ($this->request->isGet()) {
            $this->title = '配送区域管理';
            $this->citys = ExpressService::region();
            $this->fetch();
        } else {
            $data = $this->_vali(['nos.default' => '']);
            sysdata('plugin.shop.region.not', str2arr($data['nos']));
            $this->success('修改配送区域成功！', 'javascript:history.back()');
        }
    }

    /**
     * 添加邮费模板
     * @auth true
     */
    public function add()
    {
        $this->title = '添加邮费模板';
        ShopExpressTemplate::mForm('form');
    }

    /**
     * 编辑邮费模板
     * @auth true
     */
    public function edit()
    {
        $this->title = '编辑邮费模板';
        ShopExpressTemplate::mForm('form');
    }

    /**
     * 表单数据处理
     * @param array $data
     * @throws Exception
     */
    protected function _form_filter(array &$data)
    {
        if (empty($data['code'])) {
            $data['code'] = CodeExtend::uniqidDate(12, 'T');
        }
        if ($this->request->isGet()) {
            $this->citys = ExpressService::region(2, 1);
            $this->companys = ShopExpressCompany::items();
        } else {
            $data['company'] = arr2str($data['company'] ?? []);
        }
    }

    /**
     * 表单结果处理
     * @param boolean $result
     */
    protected function _form_result(bool $result)
    {
        if ($result && $this->request->isPost()) {
            $this->success('邮费模板保存成功！', 'javascript:history.back()');
        }
    }

    /**
     * 修改模板状态
     * @auth true
     */
    public function state()
    {
        ShopExpressTemplate::mSave($this->_vali([
            'status.in:0,1'  => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除邮费模板
     * @auth true
     */
    public function remove()
    {
        ShopExpressTemplate::mDelete();
    }
}