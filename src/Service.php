<?php

declare (strict_types=1);

namespace plugin\wemall;

use plugin\wemall\command\Users;
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
                ],
            ]
        ];
    }
}