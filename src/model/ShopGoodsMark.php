<?php

declare (strict_types=1);

namespace plugin\wemall\model;

use plugin\account\model\Abs;

/**
 * 商城商品标题模型
 * @class ShopGoodsMark
 * @package plugin\wemall\model
 */
class ShopGoodsMark extends Abs
{
    /**
     * 获取所有标签
     * @return array
     */
    public static function items(): array
    {
        return static::mk()->where(['status' => 1])->order('sort desc,id desc')->column('name');
    }
}