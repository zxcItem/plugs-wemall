<?php

declare (strict_types=1);

namespace plugin\wemall\model;

use plugin\account\model\Abs;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 快递模板数据模型
 * @class ShopExpressTemplate
 * @package plugin\wemall\model
 */
class ShopExpressTemplate extends Abs
{

    /**
     * 获取快递模板数据
     * @param boolean $allow
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function items(bool $allow = false): array
    {
        $items = $allow ? [
            'NONE' => ['code' => 'NONE', 'name' => '无需发货', 'normal' => '', 'content' => '[]', 'company' => ['_' => '虚拟产品']],
            'FREE' => ['code' => 'FREE', 'name' => '免费包邮', 'normal' => '', 'content' => '[]', 'company' => ['ALL' => '发货时选快递公司']],
        ] : [];
        $query = self::mk()->where(['status' => 1, 'deleted' => 0])->order('sort desc,id desc');
        foreach ($query->field('code,name,normal,content,company')->cursor() as $tmpl) $items[$tmpl->getAttr('code')] = $tmpl->toArray();
        return $items;
    }

    /**
     * 写入快递公司
     * @param mixed $value
     * @return string
     */
    public function setCompanyAttr($value): string
    {
        return is_array($value) ? arr2str($value) : $value;
    }

    /**
     * 快递公司处理
     * @param mixed $value
     * @return array
     */
    public function getCompanyAttr($value): array
    {
        [$express, $skey] = [[], 'plugin.shop.companys'];
        $companys = sysvar($skey) ?: sysvar($skey, ShopExpressCompany::items());
        foreach (is_string($value) ? str2arr($value) : (array)$value as $key) {
            if (isset($companys[$key])) $express[$key] = $companys[$key];
        }
        return $express;
    }

    /**
     * 格式化规则数据
     * @param mixed $value
     * @return array
     */
    public function getNormalAttr($value): array
    {
        return $this->getExtraAttr($value);
    }

    public function setNormalAttr($value): string
    {
        return $this->setExtraAttr($value);
    }

    /**
     * 格式化规则数据
     * @param mixed $value
     * @return array
     */
    public function getContentAttr($value): array
    {
        return $this->getExtraAttr($value);
    }

    public function setContentAttr($value): string
    {
        return $this->setExtraAttr($value);
    }
}