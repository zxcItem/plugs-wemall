<?php


declare (strict_types=1);

namespace plugin\wemall\model;


use think\model\relation\HasMany;

/**
 * 商城优惠券模型
 * @class ShopConfigCoupon
 * @package plugin\wemall\model
 */
class ShopConfigCoupon extends AbsUser
{

// 卡券类型
    public const types = ['通用券', '商品券'];

    /**
     * 关联自己的卡券
     * @return HasMany
     */
    public function usable(): HasMany
    {
        return $this->hasMany(AccountUserCoupon::class, 'coid', 'id')->where(['deleted' => 0]);
    }

    /**
     * 获取等级限制
     * @param mixed $value
     * @return array
     */
    public function getLimitLevelsAttr($value): array
    {
        return is_string($value) ? str2arr($value) : [];
    }

    /**
     * 设置等级限制
     * @param mixed $value
     * @return string
     */
    public function setLimitLevelsAttr($value): string
    {
        return is_array($value) ? arr2str($value) : $value;
    }

    /**
     * 输出格式化数据
     * @return array
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        if (isset($data['type'])) {
            $data['type_name'] = self::types[$data['type']] ?? $data['type'];
        }
        return $data;
    }
}