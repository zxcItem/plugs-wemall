<?php

declare (strict_types=1);

namespace plugin\wemall\controller\api\auth\action;

use plugin\wemall\controller\api\Auth;
use plugin\wemall\model\ShopGoods;
use plugin\wemall\model\ShopActionCollect;
use plugin\wemall\service\UserAction;
use think\admin\helper\QueryHelper;
use think\db\exception\DbException;

/**
 * 用户收藏数据
 * @class Collect
 * @package plugin\wemall\controller\api\auth\action
 */
class Collect extends Auth
{
    /**
     * 提交搜索记录
     * @return void
     * @throws DbException
     */
    public function set()
    {
        $data = $this->_vali([
            'unid.value'    => $this->unid,
            'gcode.require' => '商品不能为空！'
        ]);
        $map = ['code' => $data['gcode'], 'deleted' => 0];
        $goods = ShopGoods::mk()->where($map)->findOrEmpty();
        if ($goods->isExists()) {
            UserAction::set($this->unid, $data['gcode'], 'collect');
            $this->success('收藏成功！');
        } else {
            $this->error('收藏失败！');
        }
    }

    /**
     * 获取我的搜索记录
     * @return void
     */
    public function get()
    {
        ShopActionCollect::mQuery(null, function (QueryHelper $query) {
            $query->with(['goods'])->order('sort desc');
            $query->where(['unid' => $this->unid])->like('gcode');
            [$page, $limit] = [intval(input('page', 1)), intval(input('limit', 10))];
            $this->success('我的收藏记录！', $query->page($page, false, false, $limit));
        });
    }

    /**
     * 删除收藏记录
     * @return void
     * @throws DbException
     */
    public function del()
    {
        $data = $this->_vali(['gcode.require' => '商品不能为空！']);
        UserAction::del($this->unid, $data['gcode'], 'collect');
        $this->success('删除记录成功！');
    }

    /**
     * 清空收藏记录
     * @return void
     * @throws DbException
     */
    public function clear()
    {
        ShopActionCollect::mk()->where(['unid' => $this->unid])->delete();
        UserAction::clear($this->unid, 'collect');
        $this->success('清理记录成功！');
    }
}