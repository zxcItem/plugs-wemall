<?php

declare (strict_types=1);

namespace plugin\wemall\service;

use think\admin\Exception;

/**
 * 商城配置服务
 * @class ConfigService
 * @package plugin\wemall\service
 */
class ConfigService
{

    /**
     * 商城配置缓存名
     * @var string
     */
    private static $skey = 'plugin.shop.config';

    /**
     * 页面类型配置
     * @var string[]
     */
    public static $pageTypes = [
        'user_agreement' => '用户使用协议'
    ];

    /**
     * 读取商城配置参数
     * @param string|null $name
     * @param $default
     * @return array|mixed|null
     * @throws Exception
     */
    public static function get(?string $name = null, $default = null)
    {
        $syscfg = sysvar(self::$skey) ?: sysvar(self::$skey, sysdata(self::$skey));
        if (empty($syscfg['domain'])) $syscfg['domain'] = sysconf('base.site_host') . '/h5';
        return is_null($name) ? $syscfg : ($syscfg[$name] ?? $default);
    }

    /**
     * 保存商城配置参数
     * @param array $data
     * @return mixed
     * @throws Exception
     */
    public static function set(array $data)
    {
        // 修改前端域名处理
        if (!empty($data['domain'])) {
            $data['domain'] = rtrim($data['domain'], '\\/');
        }
        // 自动处理减免金额范围
        if (!empty($data['enable_reduct'])) {
            $reducts = [floatval($data['reduct_min'] ?? 0), floatval($data['reduct_max'] ?? 0)];
            [$data['reduct_min'], $data['reduct_max']] = [min($reducts), max($reducts)];
        }
        return sysdata(self::$skey, $data);
    }

    /**
     * 设置页面数据
     * @param string $code 页面编码
     * @param array $data 页面内容
     * @return mixed
     * @throws Exception
     */
    public static function setPage(string $code, array $data)
    {
        return sysdata("plugin.shop.page.{$code}", $data);
    }

    /**
     * 获取页面内容
     * @param string $code
     * @return array
     * @throws Exception
     */
    public static function getPage(string $code): array
    {
        return sysdata("plugin.shop.page.{$code}");
    }
}