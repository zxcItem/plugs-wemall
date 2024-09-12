<?php

declare (strict_types=1);

namespace plugin\wemall\controller\api;

use plugin\shop\model\PluginShopGoods;
use plugin\shop\model\PluginShopGoodsCate;
use plugin\wemall\model\PluginWemallUserCoupon;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\db\Query;

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
        $this->coupon = null;
        $this->cnames = null;
        PluginShopGoods::mQuery(null, function (QueryHelper $query) {
            // 根据优惠券展示商品
            if ($couponCode = input('coupon')) {
                $where = ['code' => $couponCode, 'deleted' => 0];
                $userCoupon = PluginWemallUserCoupon::mk()->where($where)->findOrEmpty();
                if ($userCoupon->isEmpty()) $this->error('无效优惠券！');
                // 追加卡券信息到商品信息
                $map = ['status' => 1, 'deleted' => 0];
                $this->coupon = $userCoupon->coupon()->where($map)->field('type,name,extra,amount,limit_amount,limit_times')->findOrEmpty()->toArray();
                if (empty($this->coupon)) $this->error('优惠券已停用！');
                if ($this->coupon['type'] == 1) {
                    $gcodes = array_column($this->coupon['extra'], 'code');
                    count($gcodes) > 0 ? $query->whereIn('code', $gcodes) : $query->whereRaw('1<>0');
                }
                unset($this->coupon['extra']);
            }
            // 根据多标签内容过滤
            if (!empty($vMarks = input('vmarks'))) {
                $query->where('marks', 'like', array_map(function ($mark) {
                    return "%,{$mark},%";
                }, str2arr($vMarks)), 'OR');
            }
            // 显示分类显示
            if (!empty($vCates = input('cates'))) {
                $cates = array_filter(PluginShopGoodsCate::items(), function ($v) use ($vCates) {
                    return $v['id'] == $vCates;
                });
                $this->cnames = null;
                if (count($cates) > 0) {
                    $cate = array_pop($cates);
                    $this->cnames = array_combine($cate['ids'], $cate['names']);
                }
            }
            $query->equal('code')->like('name#keys')->like('marks,cates', ',');
            if (!empty($code = input('code'))) {
                // 查询单个商品详情
                $query->with(['discount', 'items', 'comments' => function (Query $query) {
                    $query->limit(2)->where(['status' => 1, 'deleted' => 0]);
                }])->withCount(['comments' => function (Query $query) {
                    $query->where(['status' => 1, 'deleted' => 0]);
                }]);
                PluginShopGoods::mk()->where(['code' => $code])->inc('num_read')->update([]);
            } else {
                $query->with('discount')->withoutField('content');
            }
            // 数据排序处理
            $sort = intval(input('sort', 0));
            $type = intval(input('order', 0)) ? 'asc' : 'desc';
            if ($sort === 1) {
                $query->order("num_read {$type},sort {$type},id {$type}");
            } elseif ($sort === 2) {
                $query->order("price_selling {$type},sort {$type},id {$type}");
            } else {
                $query->order("sort {$type},id {$type}");
            }
            $query->where(['status' => 1, 'deleted' => 0]);
            // 查询数据分页
            $page = intval(input('page', 1));
            $limit = max(min(intval(input('limit', 20)), 60), 1);
            $this->success('获取商品数据', $query->page($page, false, false, $limit));
        });
    }

    /**
     * 数据结果处理
     * @param array $data
     * @param array $result
     * @return void
     */
    protected function _get_page_filter(array &$data, array &$result)
    {
        $result['cnames'] = $this->cnames ?? null;
        $result['coupon'] = $this->coupon ?? null;
    }

}