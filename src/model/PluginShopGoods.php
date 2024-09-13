<?php

declare (strict_types=1);

namespace plugin\wemall\model;

use plugin\shop\model\PluginShopGoods as PluginShopGoodsAbs;
use think\model\relation\HasOne;

/**
 * 分销商品管理
 * @class PluginShopGoods
 * @package plugin\wemall\model
 */
class PluginShopGoods extends PluginShopGoodsAbs
{

    /**
     * 日志名称
     * @var string
     */
    protected $oplogName = '商品';

    /**
     * 日志类型
     * @var string
     */
    protected $oplogType = '分销商城管理';

    /**
     * 关联等级
     * @return HasOne
     */
    public function level(): HasOne
    {
        return $this->hasOne(PluginWemallConfigLevel::class, 'number', 'level_upgrade')->bind([
            'level_name'    => 'name',
        ]);
    }

    /**
     * 关联等级
     * @return HasOne
     */
    public function lowvip(): HasOne
    {
        return $this->hasOne(PluginWemallConfigLevel::class, 'number', 'limit_lowvip')->bind([
            'low_name'    => 'name',
        ]);
    }

    /**
     * 关联折扣
     * @return HasOne
     */
    public function discount(): HasOne
    {
        return $this->hasOne(PluginWemallConfigDiscount::class, 'id', 'discount_id')->bind([
            'discount_name'    => 'name',
        ]);
    }

}