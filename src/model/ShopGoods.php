<?php

declare (strict_types=1);

namespace plugin\wemall\model;

use plugin\shop\model\ShopGoods as ShopGoodsModel;
use think\model\relation\HasOne;

/**
 * 商城商品数据模型
 * @class ShopGoods
 * @package plugin\wemall\model
 */
class ShopGoods extends ShopGoodsModel
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
        return $this->hasOne(ShopConfigLevel::class, 'number', 'level_upgrade')->bind([
            'level_name'    => 'name',
        ]);
    }

    /**
     * 关联等级
     * @return HasOne
     */
    public function lowvip(): HasOne
    {
        return $this->hasOne(ShopConfigLevel::class, 'number', 'limit_lowvip')->bind([
            'low_name'    => 'name',
        ]);
    }

    /**
     * 关联折扣
     * @return HasOne
     */
    public function discount(): HasOne
    {
        return $this->hasOne(ShopConfigDiscount::class, 'id', 'discount_id')->bind([
            'discount_name'    => 'name',
        ]);
    }
}