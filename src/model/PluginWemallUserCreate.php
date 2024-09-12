<?php

declare (strict_types=1);

namespace plugin\wemall\model;

use plugin\account\model\PluginAccountUser;
use plugin\shop\model\AbsUser;
use think\model\relation\HasOne;

/**
 * 手动创建会员用户模型
 * @class PluginWemallUserCreate
 * @package plugin\wemall\model
 */
class PluginWemallUserCreate extends AbsUser
{
    /**
     * 关联代理用户
     * @return \think\model\relation\HasOne
     */
    public function agent(): HasOne
    {
        return $this->hasOne(PluginAccountUser::class, 'phone', 'phone');
    }
}