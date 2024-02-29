<?php


declare (strict_types=1);

namespace plugin\wemall\controller\api;

use plugin\account\controller\api\Auth as AccountAuth;
use plugin\wemall\model\ShopUserRelation;

/**
 * 基础授权控制器
 * @class Auth
 * @package plugin\wemall\controller\api
 */
abstract class Auth extends AccountAuth
{
    protected $relation = [];
    protected $levelCode;
    protected $levelName;

    /**
     * 控制器初始化
     * @return void
     */
    protected function initialize()
    {
        parent::initialize();
        $this->checkUserStatus()->withUserRelation();
    }

    /**
     * 初始化当前用户
     * @return static
     */
    protected function withUserRelation(): Auth
    {
        $relation = ShopUserRelation::mk()->where(['unid' => $this->unid])->findOrEmpty();
        $this->relation = $relation->toArray();
        $this->levelCode = intval($relation->getAttr('level_code'));
        $this->levelName = $relation->getAttr('level_name') ?: '普通用户';
        if ($relation->getAttr('level_name') !== $this->levelName) {
            $relation->save(['level_name' => $this->levelName]);
        }
        return $this;
    }
}