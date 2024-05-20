<?php

declare (strict_types=1);

namespace plugin\wemall;

use plugin\payment\model\PaymentRecord;
use plugin\payment\model\PluginPaymentRecord;
use plugin\shop\model\ShopOrder;
use plugin\wemall\command\Users;
use plugin\wemall\service\UserOrder;
use plugin\wemall\service\UserRebate;
use plugin\wemall\service\UserUpgrade;
use think\admin\Plugin;

/**
 * 插件服务注册
 * @class Service
 * @package plugin\wemall
 */
class Service extends Plugin
{
    /**
     * 定义插件名称
     * @var string
     */
    protected $appName = '分销商城';

    /**
     * 定义安装包名
     * @var string
     */
    protected $package = 'xiaochao/plugs-wemall';

    /**
     * 插件服务注册
     * @return void
     */
    public function register(): void
    {
        $this->commands([Users::class]);

        // 注册支付完成事件
        $this->app->event->listen('PluginWeMallOrderUpgrade', function ($order) {
            $this->app->log->notice("Event PluginWeMallOrderUpgrade {$order}");
            UserOrder::upgrade($order);
        });

        // 注册订单确认事件
        $this->app->event->listen('PluginWeMallOrderConfirm', function ($order) {
            $this->app->log->notice("Event PluginWeMallOrderConfirm {$order}");
            UserOrder::confirm($order);
        });

        // 订单返佣处理
        $this->app->event->listen('PluginWeMallUserRebateCreate', function ($order) {
            $this->app->log->notice("Event PluginWeMallUserRebateCreate {$order}");
            UserRebate::create($order);
        });
        // 升级用户等级
        $this->app->event->listen('PluginWeMallUserRebateUpgrade', function ($order) {
            $this->app->log->notice("Event PluginWeMallUserRebateUpgrade {$order}");
            UserOrder::upgrade($order);
        });
        // 取消订单返佣处理
        $this->app->event->listen('PluginWeMallOrderUserRebateCancel', function ($order) {
            $this->app->log->notice("Event PluginWeMallOrderUserRebateCancel {$order}");
            UserRebate::cancel($order);
        });
        // 取消升级用户等级
        $this->app->event->listen('PluginWeMallOrderUserUpgradeUpgrade', function ($unid) {
            $this->app->log->notice("Event PluginWeMallOrderUserUpgradeUpgrade {$unid}");
            UserUpgrade::upgrade($unid);
        });
    }

    /**
     * 定义插件菜单
     * @return array[]
     */
    public static function menu(): array
    {
        $code = app(static::class)->appCode;
        return [
            [
                'name' => '商城配置',
                'subs' => [
                    ['name' => '数据统计报表', 'icon' => 'layui-icon layui-icon-theme', 'node' => "{$code}/base.report/index"],
                    ['name' => '商品数据管理', 'icon' => 'layui-icon layui-icon-star', 'node' => "{$code}/shop.goods/index"],
                    ['name' => '订单数据管理', 'icon' => 'layui-icon layui-icon-template', 'node' => "{$code}/shop.order/index"],
                ],
            ],
            [
                'name' => '用户返佣',
                'subs' => [
                    ['name' => '用户关系管理', 'icon' => 'layui-icon layui-icon-user', 'node' => "{$code}/user.admin/index"],
                    ['name' => '用户返佣管理', 'icon' => 'layui-icon layui-icon-transfer', 'node' => "{$code}/user.rebate/index"],
                ],
            ],
            [
                'name' => '等级折扣',
                'subs' => [
                    ['name' => '用户等级管理', 'icon' => 'layui-icon layui-icon-senior', 'node' => "{$code}/base.level/index"],
                    ['name' => '用户折扣方案', 'icon' => 'layui-icon layui-icon-engine', 'node' => "{$code}/base.discount/index"],
                    ['name' => '商城优惠券管理', 'icon' => 'layui-icon layui-icon-form', 'node' => "{$code}/base.coupon/index"],
                ],
            ]
        ];
    }
}