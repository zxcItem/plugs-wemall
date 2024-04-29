<?php

declare (strict_types=1);

namespace plugin\wemall\controller\api\auth;

use plugin\account\model\AccountUser;
use plugin\wemall\controller\api\Auth;
use plugin\shop\model\ShopConfigPoster;
use plugin\account\model\AccountRelation;
use plugin\shop\service\PosterService;
use plugin\wemall\service\UserUpgrade;
use think\admin\Exception;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\HttpResponseException;
use WeChat\Exceptions\InvalidResponseException;
use WeChat\Exceptions\LocalCacheException;

/**
 * 推广用户管理
 * @class Spread
 * @package plugin\wemall\controller\api\auth
 */
class Spread extends Auth
{
    /**
     * 获取我推广的用户
     * @return void
     */
    public function get()
    {
        AccountRelation::mQuery(null, function (QueryHelper $query) {
            // 用户搜索查询
            $db = AccountUser::mQuery()->like('phone|nickname#keys')->db();
            if ($db->getOptions('where')) $query->whereRaw("unid in {$db->field('id')->buildSql()}");
            // 数据条件查询
            $query->with(['bindUser'])->where(['puid1' => $this->unid])->order('id desc');
            $this->success('获取数据成功！', $query->page(intval(input('page', 1)), false, false, 10));
        });
    }

    /**
     * 临时绑定推荐人
     * @return void
     */
    public function spread()
    {
        try {
            $input = $this->_vali(['from.require' => '推荐人不能为空！']);
            $relation = UserUpgrade::bindAgent($this->unid, intval($input['from']), 0);
            $this->success('绑定推荐人成功！', $relation->toArray());
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * 获取我的海报
     * @return void
     * @throws InvalidResponseException
     * @throws LocalCacheException
     * @throws Exception
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function poster()
    {
        $account = $this->account->get();
        $data = [
            'user.spreat'   => "/pages/home/index?from={$this->unid}",
            'user.headimg'  => $account['user']['headimg'] ?? '',
            'user.nickname' => $account['user']['nickname'] ?? '',
            'user.rolename' => $this->relation->getAttr('level_name'),
        ];
        $items = ShopConfigPoster::items($this->levelCode, $this->type);
        foreach ($items as &$item) {
            $item['image'] = PosterService::create($item['image'], $item['content'], $data);
            unset($item['content']);
        }
        $this->success('获取海报成功！', $items);
    }
}