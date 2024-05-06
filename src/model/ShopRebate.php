<?php

declare (strict_types=1);

namespace plugin\wemall\model;

use plugin\account\model\Abs;
use plugin\account\model\AccountUser;
use think\model\relation\HasOne;

/**
 * 用户返佣模型
 * @class ShopRebate
 * @package plugin\wemall\model
 */
class ShopRebate extends Abs
{
    /**
     * 关联当前用户
     * @return HasOne
     */
    public function user(): HasOne
    {
        return $this->hasOne(AccountUser::class, 'id', 'unid');
    }

    /**
     * 关联当前用户
     * @return HasOne
     */
    public function ouser(): HasOne
    {
        return $this->hasOne(AccountUser::class, 'id', 'order_unid');
    }

}