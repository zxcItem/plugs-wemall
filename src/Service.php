<?php

declare (strict_types=1);

namespace plugin\wemall;

use plugin\account\model\PluginAccountUser;
use plugin\wemall\command\Users;
use plugin\wemall\model\PluginWemallUserRelation;
use plugin\wemall\service\UserOrder;
use plugin\wemall\service\UserRebate;
use plugin\wemall\service\UserUpgrade;
use think\admin\Plugin;
use think\exception\HttpResponseException;
use think\Request;

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
        // 注册时填写推荐时检查
        $this->app->middleware->add(function (Request $request, \Closure $next) {
            $input = $request->post(['from', 'phone', 'fphone']);
            if (!empty($input['phone']) && !empty($input['fphone'])) {
                $showError = static function ($message, array $data = []) {
                    throw new HttpResponseException(json(['code' => 0, 'info' => lang($message), 'data' => $data]));
                };
                $where = ['deleted' => 0];
                if (preg_match('/^1\d{10}$/', $input['fphone'])) {
                    $where['phone'] = $input['fphone'];
                } else {
                    if (empty($input['from'])) $showError('无效推荐人');
                    $where['id'] = $input['from'];
                }
                // 判断推荐人是否可
                $from = PluginAccountUser::mk()->where($where)->findOrEmpty();
                if ($from->isEmpty()) $showError('无效邀请人！');
                if ($from->getAttr('phone') == $input['phone']) $showError('不能邀请自己！');
                [$rela] = PluginWemallUserRelation::withRelation($from->getAttr('id'));
                if (empty($rela['entry_agent'])) $showError('无邀请权限！');
                // 检查自己是否已绑定
                $where = ['phone' => $input['phone'], 'deleted' => 0];
                if (($user = PluginAccountUser::mk()->where($where)->findOrEmpty())->isExists()) {
                    [$rela] = PluginWemallUserRelation::withRelation($user->getAttr('id'));
                    if (!empty($rela['puid1']) && $rela['puid1'] != $from->getAttr('id')) {
                        $showError('该用户已注册');
                    }
                }
            }
            return $next($request);
        }, 'route');

        // 注册用户绑定事件
        $this->app->event->listen('PluginAccountBind', function (array $data) {
            $this->app->log->notice("Event PluginAccountBind {$data['unid']}#{$data['usid']}");
            // 初始化用户关系数据
            PluginWemallUserRelation::withInit(intval($data['unid']));
            // 尝试临时绑定推荐人用户
            $input = $this->app->request->post(['from', 'phone', 'fphone']);
            if (!empty($input['fphone'])) try {
                $map = ['deleted' => 0];
                if (preg_match('/^1\d{10}$/', $input['fphone'])) {
                    $map['phone'] = $input['fphone'];
                } else {
                    $map['id'] = $input['from'] ?? 0;
                }
                $from = PluginAccountUser::mk()->where($map)->value('id');
                if ($from > 0) UserUpgrade::bindAgent(intval($data['unid']), $from, 0);
            } catch (\Exception $exception) {
                trace_file($exception);
            }
        });

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
                    ['name' => '会员折扣方案', 'icon' => 'layui-icon layui-icon-engine', 'node' => "{$code}/base.discount/index"],
                    ['name' => '商品数据管理', 'icon' => 'layui-icon layui-icon-star', 'node' => "{$code}/shop.goods/index"],
                ],
            ],
            [
                'name' => '会员代理',
                'subs' => [
                    ['name' => '会员用户管理', 'icon' => 'layui-icon layui-icon-user', 'node' => "{$code}/user.admin/index"],
                    ['name' => '会员等级管理', 'icon' => 'layui-icon layui-icon-water', 'node' => "{$code}/base.level/index"],
                    ['name' => '创建会员用户', 'icon' => 'layui-icon layui-icon-tabs', 'node' => "{$code}/user.create/index"],
                    ['name' => '代理等级管理', 'icon' => 'layui-icon layui-icon-water', 'node' => "{$code}/base.agent/index"],
                    ['name' => '代理返佣管理', 'icon' => 'layui-icon layui-icon-transfer', 'node' => "{$code}/user.rebate/index"],
                ],
            ]
        ];
    }
}