<?php


namespace plugin\wemall\model;


use plugin\account\model\AccountUser;
use plugin\wemall\service\UserOrder;
use plugin\wemall\service\UserUpgrade;
use think\Exception;
use think\model\relation\HasOne;

class AccountRelation  extends AbsUser
{
    /**
     * 关联上1级用户
     * @return HasOne
     */
    public function user1(): HasOne
    {
        return $this->hasOne(AccountUser::class, 'id', 'puid1');
    }

    /**
     * 关联上2级用户
     * @return HasOne
     */
    public function user2(): HasOne
    {
        return $this->hasOne(AccountUser::class, 'id', 'puid2');
    }

    /**
     * 关联上1级关系
     * @return HasOne
     */
    public function agent1(): HasOne
    {
        return $this->hasOne(AccountRelation::class, 'unid', 'puid1');
    }

    /**
     * 关联上2级关系
     * @return HasOne
     */
    public function agent2(): HasOne
    {
        return $this->hasOne(AccountRelation::class, 'unid', 'puid2');
    }

    /**
     * 更新用户推荐关系
     * @param integer $unid 用户编号
     * @return $this
     * @throws \think\admin\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function withInit(int $unid): AccountRelation
    {
        $user = AccountUser::mk()->findOrEmpty($unid);
        if ($user->isEmpty()) throw new Exception("无效的用户！");
        if ($user->getAttr('deleted') > 0) throw new Exception('账号已删除！');
        $rela = static::mk()->lock(true)->where(['unid' => $unid])->findOrEmpty();
        if ($rela->isEmpty() || empty($rela->getAttr('path')) || empty($rela->getAttr('level_name'))) {
            $data = ['id' => $unid, 'unid' => $unid, 'path' => ',', 'level_name' => '普通会员', 'agent_level_name' => '普通用户'];
            if (!($rela->isExists() && $rela->save($data))) {
                // ON DUPLICATE KEY UPDATE 实现 MySQL 不重复插入
                $rela->duplicate($data)->insert($data);
                $rela = $rela->where(['unid' => $unid])->findOrEmpty();
            }
            UserOrder::entry(UserUpgrade::upgrade(UserAgent::upgrade($rela)));
        }
        return $rela;
    }

    /**
     * 转换用户关联模型
     * @param int|AccountRelation $unid
     * @return array [Relation, UNID]
     * @throws \think\admin\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function withRelation($unid): array
    {
        if (is_numeric($unid)) {
            return [self::withInit(intval($unid)), intval($unid)];
        } elseif ($unid instanceof self) {
            return [$unid, intval($unid->getAttr('unid'))];
        } else {
            throw new Exception('无效的参数数据！');
        }
    }
}