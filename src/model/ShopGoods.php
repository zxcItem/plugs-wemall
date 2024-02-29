<?php

declare (strict_types=1);

namespace plugin\wemall\model;

use plugin\account\model\Abs;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\model\relation\HasMany;

/**
 * 商城商品数据模型
 * @class ShopGoods
 * @package plugin\wemall\model
 */
class ShopGoods extends Abs
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
     * 关联产品规格
     * @return HasMany
     */
    public function items(): HasMany
    {
        return static::mk()
            ->hasMany(ShopGoodsItem::class, 'gcode', 'code')
            ->withoutField('id,status,create_time,update_time')
            ->where(['status' => 1]);
    }

    /**
     * 关联产品列表
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function lists(): array
    {
        $model = static::mk()->with('items')->withoutField('specs');
        return $model->order('sort desc,id desc')->where(['deleted' => 0])->select()->toArray();
    }

    /**
     * 标签处理
     * @param mixed $value
     * @return array
     */
    public function getMarksAttr($value): array
    {
        $ckey = 'PluginWemallGoodsMarkItems';
        $items = sysvar($ckey) ?: sysvar($ckey, ShopGoodsMark::items());
        return str2arr(is_array($value) ? arr2str($value) : $value, ',', $items);
    }

    /**
     * 处理商品分类数据
     * @param mixed $value
     * @return array
     */
    public function getCatesAttr($value): array
    {
        $ckey = 'PluginWemallGoodsCateItem';
        $cates = sysvar($ckey) ?: sysvar($ckey, ShopGoodsCate::items(true));
        $cateids = is_string($value) ? str2arr($value) : (array)$value;
        foreach ($cates as $cate) if (in_array($cate['id'], $cateids)) return $cate;
        return [];
    }

    public function getSliderAttr($value): array
    {
        return is_string($value) ? str2arr($value, '|') : [];
    }

    public function setSpecsAttr($value): string
    {
        return $this->setExtraAttr($value);
    }

    public function getSpecsAttr($value): array
    {
        return $this->getExtraAttr($value);
    }
}