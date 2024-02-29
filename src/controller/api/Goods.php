<?php

declare (strict_types=1);

namespace plugin\wemall\controller\api;

use plugin\wemall\model\ShopGoods;
use plugin\wemall\model\ShopGoodsCate;
use plugin\wemall\model\ShopGoodsMark;
use plugin\wemall\model\ShopActionSearch;
use plugin\wemall\service\ExpressService;
use think\admin\Controller;
use think\admin\Exception;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 获取商品数据接口
 * @class Goods
 * @package plugin\wemall\controller\api
 */
class Goods extends Controller
{

    /**
     * 获取商品列表或详情
     * @return void
     */
    public function get()
    {
        ShopGoods::mQuery(null, function (QueryHelper $query) {
            $query->equal('code')->like('name')->like('marks,cates', ',');
            if (!empty($code = input('code'))) {
                $query->with('items');
                ShopGoods::mk()->where(['code' => $code])->inc('num_read')->update([]);
            } else {
                $query->withoutField('content');
            }
            $sort = intval(input('sort', 0));
            if ($sort === 1) {
                $query->order('num_read desc,sort desc,id desc');
            } elseif ($sort === 2) {
                $query->order('price_selling desc,sort desc,id desc');
            } else {
                $query->order('sort desc,id desc');
            }
            $query->where(['status' => 1, 'deleted' => 0]);
            $this->success('获取商品数据', $query->page(intval(input('page', 1)), false, false, 10));
        });
    }

    /**
     * 获取商品分类及标签
     * @return void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function cate()
    {
        $this->success('获取分类成功', [
            'mark' => ShopGoodsMark::items(),
            'cate' => ShopGoodsCate::treeData(),
        ]);
    }

    /**
     * 获取物流配送区域
     * @return void
     * @throws Exception
     */
    public function region()
    {
        $this->success('获取配送区域', ExpressService::region(3, 1));
    }

    /**
     * 获取搜索热词
     * @return void
     */
    public function hotkeys()
    {
        ShopActionSearch::mQuery(null, function (QueryHelper $query) {
            $query->whereTime('sort', '-30 days')->like('keys');
            $query->field('keys')->group('keys')->cache(true, 60)->order('sort desc');
            $this->success('获取搜索热词！', ['keys' => $query->limit(0, 15)->column('keys')]);
        });
    }
}