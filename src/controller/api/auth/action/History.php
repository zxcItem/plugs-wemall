<?php

declare (strict_types=1);

namespace plugin\wemall\controller\api\auth\action;

use plugin\wemall\controller\api\Auth;
use plugin\wemall\model\ShopGoods;
use plugin\wemall\model\ShopActionHistory;
use plugin\wemall\service\UserAction;
use think\admin\helper\QueryHelper;
use think\db\exception\DbException;

/**
 * 用户足迹数据
 * @class History
 * @package plugin\wemall\controller\api\auth\action
 */
class History extends Auth
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
        if (ShopGoods::mk()->where($map)->findOrEmpty()->isExists()) {
            UserAction::set($this->unid, $data['gcode'], 'history');
            $this->success('添加成功！');
        } else {
            $this->error('添加失败！');
        }
    }

    /**
     * 获取我的访问记录
     * @return void
     */
    public function get()
    {
        ShopActionHistory::mQuery(null, function (QueryHelper $query) {
            $query->with(['goods'])->order('sort desc');
            $query->where(['unid' => $this->unid])->like('gcode');
            [$page, $limit] = [intval(input('page', 1)), intval(input('limit', 10))];
            $this->success('我的访问记录！', $query->page($page, false, false, $limit));
        });
    }

    /**
     * 删除收藏记录
     * @return void
     * @throws DbException
     */
    public function del()
    {
        $data = $this->_vali(['gcode.require' => '编号不能为空！']);
        UserAction::del($this->unid, $data['gcode'], 'history');
        $this->success('删除记录成功！');
    }

    /**
     * 清空访问记录
     * @return void
     * @throws DbException
     */
    public function clear()
    {
        UserAction::clear($this->unid, 'history');
        $this->success('清理记录成功！');
    }
}