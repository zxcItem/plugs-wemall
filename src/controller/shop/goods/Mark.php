<?php


declare (strict_types=1);

namespace plugin\wemall\controller\shop\goods;

use plugin\wemall\model\ShopGoodsMark;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 商品标签管理
 * @class Mark
 * @package plugin\wemall\controller\shop\goods
 */
class Mark extends Controller
{
    /**
     * 商品标签管理
     * @auth true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        ShopGoodsMark::mQuery($this->get)->layTable(function () {
            $this->title = '商品标签管理';
        }, static function (QueryHelper $query) {
            $query->like('name')->equal('status')->dateBetween('create_at');
        });
    }

    /**
     * 添加商品标签
     * @auth true
     */
    public function add()
    {
        ShopGoodsMark::mForm('form');
    }

    /**
     * 编辑商品标签
     * @auth true
     */
    public function edit()
    {
        ShopGoodsMark::mForm('form');
    }

    /**
     * 修改商品标签状态
     * @auth true
     */
    public function state()
    {
        ShopGoodsMark::mSave();
    }

    /**
     * 删除商品标签
     * @auth true
     */
    public function remove()
    {
        ShopGoodsMark::mDelete();
    }

    /**
     * 商品标签选择kkd
     * @login true
     * @return void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function select()
    {
        $this->get['status'] = 1;
        $this->get['deleted'] = 0;
        $this->index();
    }
}