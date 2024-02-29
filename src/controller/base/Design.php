<?php


declare (strict_types=1);

namespace plugin\wemall\controller\base;

use think\admin\Controller;
use think\admin\Exception;

/**
 * 页面设计器
 * @class Design
 * @package plugin\wemall\controller\base
 */
class Design extends Controller
{
    /**
     * 前端页面设计
     * @auth true
     * @menu true
     * @return void
     * @throws Exception
     */
    public function index()
    {
        $this->title = '店铺页面装修 ( 注意：后端页面显示与前端展示可能有些误差，请以前端实际显示为准！ )';
        $this->data = sysdata('plugin.shop.design');
        $this->fetch();
    }

    /**
     * 保存页面布局
     * @auth true
     * @return void
     * @throws Exception
     */
    public function save()
    {
        $input = $this->_vali([
            'pages.require'  => '页面配置不能为空！',
            'navbar.require' => '菜单导航配置不能为空！'
        ]);
        sysdata('plugin.shop.design', [
            'pages'  => json_decode($input['pages'], true),
            'navbar' => json_decode($input['navbar'], true)
        ]);
        $this->success('保存成功！');
    }

    /**
     * 连接选择器
     * @login true
     * @return void
     */
    public function link()
    {
        $this->types = [
            ['name' => '商品分类', 'link' => sysuri('plugin-wemall/shop.goods.cate/select')],
            ['name' => '商品标签', 'link' => sysuri('plugin-wemall/shop.goods.mark/select')],
            ['name' => '商品详情', 'link' => sysuri('plugin-wemall/shop.goods/select')],
        ];
        $this->fetch();
    }
}