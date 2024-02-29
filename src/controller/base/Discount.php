<?php


declare (strict_types=1);

namespace plugin\wemall\controller\base;

use plugin\wemall\model\ShopConfigDiscount;
use plugin\wemall\model\ShopConfigLevel;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 折扣方案管理
 * @class Discount
 * @package plugin\wemall\controller\base
 */
class Discount extends Controller
{
    /**
     * 折扣方案管理
     * @auth true
     * @menu true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        $this->type = $this->get['type'] ?? 'index';
        ShopConfigDiscount::mQuery()->layTable(function () {
            $this->title = '折扣方案管理';
        }, function (QueryHelper $query) {
            $query->where(['status' => intval($this->type === 'index'), 'deleted' => 0]);
        });
    }

    /**
     * 添加折扣方案
     * @auth true
     */
    public function add()
    {
        ShopConfigDiscount::mForm('form');
    }

    /**
     * 编辑折扣方案
     * @auth true
     */
    public function edit()
    {
        ShopConfigDiscount::mForm('form');
    }

    /**
     * 表单数据处理
     * @param array $vo
     */
    protected function _form_filter(array &$vo)
    {
        if ($this->request->isPost()) {
            $rule = [];
            foreach ($vo as $k => $v) if (stripos($k, '_level_') !== false) {
                [, $level] = explode('_level_', $k);
                $rule[] = ['level' => $level, 'discount' => $v];
            }
            $vo['items'] = json_encode($rule, JSON_UNESCAPED_UNICODE);
        } else {
            $this->levels = ShopConfigLevel::items();
            if (empty($this->levels)) $this->error('未配置用户等级！');
            foreach ($vo['items'] ?? [] as $item) {
                $vo["_level_{$item['level']}"] = $item['discount'];
            }
        }
    }

    /**
     * 修改折扣方案状态
     * @auth true
     */
    public function state()
    {
        ShopConfigDiscount::mSave();
    }

    /**
     * 删除折扣方案配置
     * @auth true
     */
    public function remove()
    {
        ShopConfigDiscount::mDelete();
    }
}