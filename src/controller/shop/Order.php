<?php


declare (strict_types=1);

namespace plugin\wemall\controller\shop;

use plugin\account\model\AccountUser;
use plugin\payment\service\Payment;
use plugin\shop\model\ShopOrder;
use plugin\shop\model\ShopOrderSend;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 订单数据管理
 * @class Order
 * @package plugin\wemall\controller\shop
 */
class Order extends Controller
{
    /**
     * 支付方式
     * @var array
     */
    protected $payments = [];

    /**
     * 控制器初始化
     */
    protected function initialize()
    {
        parent::initialize();
        $this->payments = Payment::types();
    }

    /**
     * 订单数据管理
     * @auth true
     * @menu true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        ShopOrder::mQuery()->where('puid1','>=',0)->layTable(function (QueryHelper $query) {
            $this->title = '订单数据管理';
            $this->total = ['t0' => 0, 't1' => 0, 't2' => 0, 't3' => 0, 't4' => 0, 't5' => 0, 't6' => 0, 't7' => 0, 'ta' => 0];
            $this->types = ['ta' => '全部订单', 't2' => '待支付', 't3' => '待审核', 't4' => '待发货', 't5' => '已发货', 't6' => '已收货', 't7' => '已评论', 't0' => '已取消'];
            foreach ($query->db()->field('status,count(1) total')->group('status')->cursor() as $vo) {
                [$this->total["t{$vo['status']}"] = $vo['total'], $this->total['ta'] += $vo['total']];
            }
        }, function (QueryHelper $query) {
            $query->with(['user', 'items', 'address']);
            $query->equal('status')->like('order_no');
            $query->dateBetween('create_time,payment_time,cancel_time,delivery_type');

            // 发货信息搜索
            $db = ShopOrderSend::mQuery()->dateBetween('express_time')
                ->like('user_name|user_phone|region_prov|region_city|region_area|region_addr#address')->db();
            if ($db->getOptions('where')) $query->whereRaw("order_no in {$db->field('order_no')->buildSql()}");

            // 用户搜索查询
            $db = AccountUser::mQuery()->like('phone|nickname#user_keys')->db();
            if ($db->getOptions('where')) $query->whereRaw("unid in {$db->field('id')->buildSql()}");

            // 代理搜索查询
            $db = AccountUser::mQuery()->like('phone|nickname#from_keys')->db();
            if ($db->getOptions('where')) $query->whereRaw("puid1 in {$db->field('id')->buildSql()}");
            // 分页排序处理
            $query->where(['deleted_status' => 0]);
        });
    }
}