<?php


namespace plugin\wemall\model;


use plugin\account\model\AccountUser;
use think\model\relation\HasOne;

/**
 * 手动创建会员用户模型
 * @class AccountUserCreate
 * @package plugin\wemall\model
 */
class AccountUserCreate extends AbsUser
{
    /**
     * 关联代理用户
     * @return HasOne
     */
    public function agent(): HasOne
    {
        return $this->hasOne(AccountUser::class, 'phone', 'phone');
    }
}