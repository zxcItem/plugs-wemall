<?php

declare (strict_types=1);

namespace plugin\wemall\model;

use plugin\account\model\Abs;
use plugin\account\service\Account;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\db\Query;

/**
 * 推广海报数据模型
 * @class ShopConfigPoster
 * @package plugin\wemall\model
 */
class ShopConfigPoster extends Abs
{

    /**
     * 指定用户等级获取配置
     * @param integer $level 指定用户等级
     * @param string $device 指定终端类型
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function items(int $level, string $device = ''): array
    {
        // 指定用户等级查看终端授权
        $query = self::mk()->where(static function (Query $query) use ($level) {
            $query->whereOr([['levels', 'like', "%,{$level},%"], ['levels', 'like', '%,-,%']]);
        })->where(['status' => 1, 'deleted' => 0])->order('sort desc,id desc');
        // 指定设备终端授权数据筛选
        if ($device !== '') $query->where(static function (Query $query) use ($device) {
            $query->whereOr([['devices', 'like', "%,{$device},%"], ['devices', 'like', '%,-,%']]);
        });
        return $query->withoutField('sort,status,deleted')->select()->toArray();
    }

    /**
     * 获取用户等级数据
     * @param mixed $value
     * @return array
     */
    public function getLevelsAttr($value): array
    {
        return is_string($value) ? str2arr($value) : [];
    }

    /**
     * 设置用户等级数据
     * @param mixed $value
     * @return string
     */
    public function setLevelsAttr($value): string
    {
        return is_array($value) ? arr2str($value) : $value;
    }

    /**
     * 获取授权终端设备
     * @param mixed $value
     * @return array
     */
    public function getDevicesAttr($value): array
    {
        return $this->getLevelsAttr($value);
    }

    /**
     * 格式化数据写入
     * @param mixed $value
     * @return string
     */
    public function setDevicesAttr($value): string
    {
        return is_array($value) ? arr2str($value) : $value;
    }

    /**
     * 格式化定位数据
     * @param mixed $value
     */
    public function getContentAttr($value): array
    {
        return $this->getExtraAttr($value);
    }

    public function setContentAttr($value): string
    {
        return $this->setExtraAttr($value);
    }

    /**
     * 数据名称转换处理
     * @return array
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        if (isset($data['levels'])) {
            $data['levels_names'] = [];
            $types = array_column(ShopConfigLevel::items(), 'name', 'number');
            if (in_array('-', $data['levels'])) $data['levels_names'] = ['全部'];
            else foreach ($data['levels'] as $k) $data['levels_names'][] = $types[$k] ?? $k;
        }
        if (isset($data['devices'])) {
            $data['devices_names'] = [];
            $types = array_column(Account::types(), 'name', 'code');
            if (in_array('-', $data['devices'])) $data['devices_names'] = ['全部'];
            else foreach ($data['devices'] as $k) $data['devices_names'][] = $types[$k] ?? $k;
        }
        return $data;
    }
}