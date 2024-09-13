<?php

declare (strict_types=1);

namespace plugin\wemall\controller\shop;

use plugin\wemall\model\PluginWemallConfigDiscount;
use plugin\wemall\model\PluginWemallConfigLevel;
use plugin\shop\model\PluginShopExpressTemplate;
use plugin\shop\model\PluginShopGoodsCate;
use plugin\shop\model\PluginShopGoodsMark;
use plugin\shop\service\ConfigService;
use plugin\wemall\model\PluginShopGoods;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 商品数据管理
 * @class Goods
 * @package plugin\wemall\controller\shop
 */
class Goods extends Controller
{
    /**
     * 商品数据管理
     * @auth true
     * @menu true
     * @return void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        $this->type = $this->request->get('type', 'index');
        PluginShopGoods::mQuery($this->get)->layTable(function () {
            $this->title = '商品数据管理';
            $this->cates = PluginShopGoodsCate::items();
            $this->marks = PluginShopGoodsMark::items();
            $this->upgrades = PluginWemallConfigLevel::items('普通商品');
            $this->deliverys = PluginShopExpressTemplate::items(true);
            $this->enableBalance = ConfigService::get('enable_balance');
            $this->enableIntegral = ConfigService::get('enable_integral');
        }, function (QueryHelper $query) {
            $query->with(['level','discount','lowvip'])->withoutField('specs,content')->like('code|name#name')->like('marks,cates', ',');
            $query->equal('status,level_upgrade,delivery_code,rebate_type')->dateBetween('create_time');
            $query->where(['status' => intval($this->type === 'index'), 'deleted' => 0]);
        });
    }

    /**
     * 配置商品返佣
     * @auth true
     */
    public function edit()
    {
        PluginShopGoods::mForm('form', 'code');
    }

    /**
     * 表单数据处理
     * @param array $data
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    protected function _form_filter(array &$data)
    {
        if ($this->request->isGet()) {
            $this->upgrades = PluginWemallConfigLevel::items('普通商品');
            $this->discounts = PluginWemallConfigDiscount::items(true);
        }
    }
}