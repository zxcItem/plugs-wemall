<?php

declare (strict_types=1);

namespace plugin\wemall\controller\api\auth;

use plugin\payment\model\PaymentAddress;
use plugin\payment\service\BalanceService;
use plugin\payment\service\contract\PaymentResponse;
use plugin\payment\service\IntegralService;
use plugin\payment\service\Payment;
use plugin\wemall\controller\api\Auth;
use plugin\shop\model\ShopOrder;
use plugin\shop\model\ShopOrderCart;
use plugin\shop\model\ShopOrderItem;
use plugin\shop\service\ConfigService;
use plugin\shop\service\ExpressService;
use plugin\wemall\service\GoodsService;
use plugin\shop\service\UserAction;
use plugin\wemall\service\UserOrder;
use plugin\wemall\service\UserUpgrade;
use think\admin\Exception;
use think\admin\extend\CodeExtend;
use think\admin\helper\QueryHelper;
use think\admin\Storage;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\db\Query;
use think\exception\HttpResponseException;

/**
 * 订单数据接口
 * @class Order
 * @package plugin\wemall\controller\api\auth
 */
class Order extends Auth
{

    /**
     * 创建订单数据
     * @return void
     */
    public function add()
    {
        try {
            // 请求参数检查
            $input = $this->_vali(['carts.default' => '', 'rules.default' => '', 'agent.default' => '0']);
            if (empty($input['rules']) && empty($input['carts'])) $this->error('参数无效！');
            // 绑定代理数据
            $order = UserUpgrade::withAgent($this->unid, intval($input['agent']), $this->relation);
            // 生成统一编号
            do $extra = ['order_no' => $order['order_no'] = CodeExtend::uniqidNumber(16, 'N')];
            while (ShopOrder::mk()->master()->where($extra)->findOrEmpty()->isExists());
            [$items, $deliveryType] = [[], 0];
            // 组装订单数据
            foreach (GoodsService::parse($this->unid, trim($input['rules'], ':;'), $input['carts']) as $item) {
                if (empty($item['count'])) continue;
                if (empty($item['goods']) || empty($item['specs'])) $this->error('商品无效！');
                [$goods, $gspec, $count] = [$item['goods'], $item['specs'], intval($item['count'])];
                // 订单物流类型
                if (empty($deliveryType) && $goods['delivery_code'] !== 'NONE') $deliveryType = 1;
                // 限制购买数量
                if (isset($goods['limit_maxnum']) && $goods['limit_maxnum'] > 0) {
                    $join = [ShopOrderItem::mk()->getTable() => 'b'];
                    $where = [['a.unid', '=', $this->unid], ['a.status', 'in', [2, 3, 4, 5]], ['b.gcode', '=', $goods['code']]];
                    $buyCount = ShopOrder::mk()->alias('a')->join($join, 'a.order_no=b.order_no')->where($where)->sum('b.stock_sales');
                    if ($buyCount + $count > $goods['limit_maxnum']) $this->error('商品限购！');
                }
                // 限制购买身份
                if ($goods['limit_lowvip'] > $this->levelCode) $this->error('等级不够！');
                // 商品库存检查
                if ($gspec['stock_sales'] + $count > $gspec['stock_total']) $this->error('库存不足！');
                // 商品折扣处理
                [$discountId, $discountRate] = UserOrder::discount($goods['discount_id'], $this->levelCode);
                // 订单详情处理
                $items[] = [
                    'unid'                  => $order['unid'],
                    'order_no'              => $order['order_no'],
                    // 商品字段
                    'gsku'                  => $gspec['gsku'],
                    'gname'                 => $goods['name'],
                    'gcode'                 => $gspec['gcode'],
                    'ghash'                 => $gspec['ghash'],
                    'gspec'                 => $gspec['gspec'],
                    'gunit'                 => $gspec['gunit'],
                    'gcover'                => empty($gspec['gimage']) ? $goods['cover'] : $gspec['gimage'],
                    // 库存数量处理
                    'stock_sales'           => $count,
                    // 快递发货数据
                    'delivery_code'         => $goods['delivery_code'],
                    'delivery_count'        => $goods['rebate_type'] > 0 ? $gspec['number_express'] * $count : 0,
                    // 商品费用字段
                    'price_cost'            => $gspec['price_cost'],
                    'price_market'          => $gspec['price_market'],
                    'price_selling'         => $gspec['price_selling'],
                    // 商品费用统计
                    'total_price_cost'      => $gspec['price_cost'] * $count,
                    'total_price_market'    => $gspec['price_market'] * $count,
                    'total_price_selling'   => $gspec['price_selling'] * $count,
                    'total_allow_balance'   => $gspec['allow_balance'] * $count,
                    'total_allow_integral'  => $gspec['allow_integral'] * $count,
                    'total_reward_balance'  => $gspec['reward_balance'] * $count,
                    'total_reward_integral' => $gspec['reward_integral'] * $count,
                    // 用户等级
                    'level_code'            => $this->levelCode,
                    'level_name'            => $this->levelName,
                    'level_upgrade'         => $goods['level_upgrade'],
                    // 是否参与返佣
                    'rebate_type'           => $goods['rebate_type'],
                    'rebate_amount'         => $goods['rebate_type'] > 0 ? $gspec['price_selling'] * $count : 0,
                    // 等级优惠方案
                    'discount_id'           => $discountId,
                    'discount_rate'         => $discountRate,
                    'discount_amount'       => $discountRate * $gspec['price_selling'] * $count / 100,
                ];
            }
            // 默认使用销售销售
            $order['rebate_amount'] = array_sum(array_column($items, 'rebate_amount'));
            $order['allow_balance'] = array_sum(array_column($items, 'total_allow_balance'));
            $order['allow_integral'] = array_sum(array_column($items, 'total_allow_integral'));
            $order['reward_balance'] = array_sum(array_column($items, 'total_reward_balance'));
            $order['reward_integral'] = array_sum(array_column($items, 'total_reward_integral'));
            // 订单发货类型
            $order['status'] = $deliveryType ? 1 : 2;
            $order['delivery_type'] = $deliveryType;
            $order['ratio_integral'] = IntegralService::ratio();
            // 统计商品数量
            $order['number_goods'] = array_sum(array_column($items, 'stock_sales'));
            $order['number_express'] = array_sum(array_column($items, 'delivery_count'));
            // 统计商品金额
            $order['amount_cost'] = array_sum(array_column($items, 'total_price_cost'));
            $order['amount_goods'] = array_sum(array_column($items, 'total_price_selling'));
            // 折扣后的金额
            $order['amount_discount'] = array_sum(array_column($items, 'discount_amount'));
            // 订单随减金额
            $order['amount_reduct'] = UserOrder::reduct();
            if ($order['amount_reduct'] > $order['amount_goods']) {
                $order['amount_reduct'] = $order['amount_goods'];
            }
            // 统计订单金额
            $order['amount_real'] = $order['amount_discount'] - $order['amount_reduct'];
            $order['amount_total'] = $order['amount_goods'];
            $order['amount_profit'] = $order['amount_real'] - $order['amount_cost'];
            // 写入商品数据
            $this->app->db->transaction(function () use ($order, $items) {
                ($model = ShopOrder::mk())->save($order);
                ShopOrderItem::mk()->saveAll($items);
                // 设置收货地址
                if ($order['delivery_type']) {
                    $where = ['unid' => $this->unid, 'deleted' => 0];
                    $address = PaymentAddress::mk()->where($where)->order('type desc,id desc')->findOrEmpty();
                    $address->isExists() && UserOrder::perfect($model->refresh(), $address);
                }
            });
            // 同步库存销量
            foreach (array_unique(array_column($items, 'gcode')) as $gcode) {
                GoodsService::stock($gcode);
            }
            // 清理购物车数据
            if (count($carts = str2arr($input['carts'])) > 0) {
                ShopOrderCart::mk()->whereIn('id', $carts)->delete();
                UserAction::recount($this->unid);
            }
            // 触发订单创建事件
            $this->app->event->trigger('PluginWemallOrderCreate', $order);
            // 无需发货且无需支付，直接完成支付流程
            if ($order['status'] === 2 && empty($order['amount_real'])) {
                Payment::emptyPayment($this->account, $order['order_no']);
                $this->success('下单成功！', ShopOrder::mk()->where(['order_no' => $order['order_no']])->findOrEmpty()->toArray());
            }
            // 返回处理成功数据
            $this->success('下单成功！', array_merge($order, ['items' => $items]));
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error("下单失败，{$exception->getMessage()}");
        }
    }
}