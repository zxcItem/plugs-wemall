<?php

declare (strict_types=1);

namespace plugin\wemall\controller\api;

use plugin\wemall\model\ShopGoods;
use think\admin\Controller;
use think\admin\helper\QueryHelper;

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
}