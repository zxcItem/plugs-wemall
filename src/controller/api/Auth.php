<?php

declare (strict_types=1);

namespace plugin\wemall\controller\api;

use plugin\shop\controller\api\Auth as ShopAuth;
use plugin\wemall\model\PluginWemallUserRelation;
use think\exception\HttpResponseException;

/**
 * 基础授权控制器
 * @class Auth
 * @package plugin\wemall\controller\api
 */
abstract class Auth extends ShopAuth
{
    /**
     * 用户关系
     * @var PluginWemallUserRelation
     */
    protected $relation;

    /**
     * 等级序号
     * @var integer
     */
    protected $levelCode;

    /**
     * 控制器初始化
     * @return void
     */
    protected function initialize()
    {
        try {
            parent::initialize();
            $this->checkUserStatus()->withUserRelation();
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * 初始化当前用户
     * @return static
     */
    protected function withUserRelation(): Auth
    {
        $this->relation = PluginWemallUserRelation::withInit($this->unid);
        $this->levelCode = intval($this->relation->getAttr('level_code'));
        return $this;
    }
}