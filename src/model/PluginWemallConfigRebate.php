<?php


declare (strict_types=1);

namespace plugin\wemall\model;

use plugin\shop\model\AbsUser;
use plugin\wemall\service\UserRebate;

/**
 * 商城返佣配置
 * @class PluginWemallConfigRebate
 * @package plugin\wemall\model
 */
class PluginWemallConfigRebate extends AbsUser
{
    /**
     * 数据输出处理
     * @return array
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        if (isset($data['type'])) {
            $data['type_name'] = UserRebate::prizes[$data['type']] ?? $data['type'];
        }
        if (isset($data['p0_level']) && isset($data['p1_level']) && isset($data['p2_level']) && isset($data['p3_level'])) {
            $levels = sysvar('plugin.wemall.levels') ?: sysvar('plugin.wemall.levels', PluginWemallConfigLevel::items());
            $data['levels'] = join(' - ', array_map(function ($v) use ($levels) {
                if ($v == -2) return '无';
                if ($v == -1) return '任意';
                return $levels[$v]['name'] ?? $v;
            }, [$data['p3_level'], $data['p2_level'], $data['p1_level'], $data['p0_level']]));
        }
        return $data;
    }
}