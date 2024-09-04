<?php

declare (strict_types=1);

namespace plugin\wemall\model;

use plugin\account\model\Abs;
use plugin\account\model\AccountUser;
use think\model\relation\HasOne;

/**
 * 用户基础模型
 * @class AbsUser
 * @package plugin\wemall\model
 */
abstract class AbsUser extends Abs
{
    /**
     * 关联当前用户
     * @return \think\model\relation\HasOne
     */
    public function user(): HasOne
    {
        return $this->hasOne(AccountUser::class, 'id', 'unid');
    }

    /**
     * 绑定用户数据
     * @return HasOne
     */
    public function bindUser(): HasOne
    {
        return $this->user()->bind([
            'user_phone'       => 'phone',
            'user_headimg'     => 'headimg',
            'user_username'    => 'username',
            'user_nickname'    => 'nickname',
            'user_create_time' => 'create_time',
        ]);
    }
}