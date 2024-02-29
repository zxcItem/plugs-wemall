<?php

namespace plugin\wemall\controller\base;

use plugin\wemall\model\ShopNewsItem;
use think\admin\Controller;
use think\admin\extend\CodeExtend;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 文章内容管理
 * Class News
 * @package plugin\wemall\controller\base
 */
class News extends Controller
{
    /**
     * 文章内容管理
     * @auth true
     * @menu true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        $this->type = $this->get['type'] ?? 'index';
        ShopNewsItem::mQuery($this->get)->layTable(function () {
            $this->title = '文章内容管理';
        }, function (QueryHelper $query) {
            $query->like('name')->dateBetween('create_time');
            $query->where(['status' => intval($this->type === 'index'), 'deleted' => 0]);
        });
    }

    /**
     * 添加文章内容
     * @auth true
     */
    public function add()
    {
        $this->title = '添加文章内容';
        ShopNewsItem::mForm('form');
    }

    /**
     * 编辑文章内容
     * @auth true
     */
    public function edit()
    {
        $this->title = '编辑文章内容';
        ShopNewsItem::mForm('form');
    }

    /**
     * 表单数据处理
     * @param array $data
     */
    protected function _form_filter(array &$data)
    {
        if (empty($data['code'])) {
            $data['code'] = CodeExtend::uniqidNumber(20, 'A');
        }
    }

    /**
     * 表单结果处理
     * @param boolean $state
     */
    protected function _form_result(bool $state)
    {
        if ($state) {
            $this->success('文章保存成功！', 'javascript:history.back()');
        }
    }

    /**
     * 修改文章状态
     * @auth true
     */
    public function state()
    {
        ShopNewsItem::mSave($this->_vali([
            'status.in:0,1'  => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除文章内容
     * @auth true
     */
    public function remove()
    {
        ShopNewsItem::mDelete();
    }

    /**
     * 文章内容选择
     * @login true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function select()
    {
        $this->get['status'] = 1;
        $this->index();
    }
}