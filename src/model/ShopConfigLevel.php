<?php

declare (strict_types=1);

namespace plugin\wemall\model;

use plugin\account\model\Abs;
use think\db\exception\DbException;

/**
 * 用户等级配置模型
 * @class ShopConfigLevel
 * @package plugin\wemall\model
 */
class ShopConfigLevel extends Abs
{
    /**
     * 获取用户等级
     * @param ?string $first 增加首项内容
     * @param string $fields 指定查询字段
     * @return array
     */
    public static function items(string $first = null, string $fields = 'name,number as prefix,number,upgrade_team'): array
    {
        $items = $first ? [-1 => ['name' => $first, 'prefix' => '-', 'number' => -1, 'upgrade_team' => 0]] : [];
        return array_merge($items, static::mk()->where(['status' => 1])->withoutField('id,utime,status,update_time,create_time')->order('number asc')->column($fields, 'number'));
    }

    /**
     * 获取最大级别数
     * @return integer
     * @throws DbException
     */
    public static function maxNumber(): int
    {
        if (static::mk()->count() < 1) return 0;
        return intval(static::mk()->max('number') + 1);
    }
}