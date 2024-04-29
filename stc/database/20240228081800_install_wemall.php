<?php

use plugin\wemall\Service;
use think\admin\extend\PhinxExtend;
use think\migration\Migrator;
use think\migration\db\Column;

class InstallWemall extends Migrator
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        $this->_create_insertMenu();
        $this->_create_shop_user_rebate();
        $this->_create_shop_user_rebate_config();
        $this->_create_shop_config_discount();
        $this->_create_shop_config_level();
    }

    /**
     * 创建菜单
     * @return void
     */
    protected function _create_insertMenu()
    {
        PhinxExtend::write2menu([
            [
                'name' => '分销管理',
                'subs' => Service::menu(),
            ],
        ], ['node' => 'plugin-wemall/base.report/index']);
    }

    /**
     * 创建数据对象
     * @class ShopConfigDiscount
     * @table shop_config_discount
     * @return void
     */
    private function _create_shop_config_discount() {

        // 当前数据表
        $table = 'shop_config_discount';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-配置-折扣',
        ])
            ->addColumn('name','string',['limit' => 200, 'default' => '', 'null' => true, 'comment' => '方案名称'])
            ->addColumn('remark','string',['limit' => 500, 'default' => '', 'null' => true, 'comment' => '方案描述'])
            ->addColumn('items','text',['default' => NULL, 'null' => true, 'comment' => '方案规则'])
            ->addColumn('sort','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
            ->addColumn('status','integer',['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '方案状态(0禁用,1使用)'])
            ->addColumn('deleted','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(1已删,0未删)'])
            ->addColumn('create_time','datetime',['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time','datetime',['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('sort', ['name' => 'idx_shop_config_discount_sort'])
            ->addIndex('status', ['name' => 'idx_shop_config_discount_status'])
            ->addIndex('deleted', ['name' => 'idx_shop_config_discount_deleted'])
            ->addIndex('create_time', ['name' => 'idx_shop_config_discount_create_time'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class ShopUserRebateConfig
     * @table shop_user_rebate_config
     * @return void
     */
    private function _create_shop_user_rebate_config()
    {

        // 当前数据表
        $table = 'shop_user_rebate_config';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-配置-返佣',
        ])
            ->addColumn('type','string',['limit' => 50, 'default' => '', 'null' => true, 'comment' => '奖励类型'])
            ->addColumn('code','string',['limit' => 50, 'default' => '', 'null' => true, 'comment' => '配置编号'])
            ->addColumn('name','string',['limit' => 180, 'default' => '', 'null' => true, 'comment' => '配置名称'])
            ->addColumn('path','string',['limit' => 180, 'default' => '', 'null' => true, 'comment' => '等级关系'])
            ->addColumn('stype','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '结算类型(0支付结算,1收货结算)'])
            ->addColumn('p0_level','biginteger',['limit' => 20, 'default' => -1, 'null' => true, 'comment' => '会员等级'])
            ->addColumn('p1_level','biginteger',['limit' => 20, 'default' => -1, 'null' => true, 'comment' => '上1级等级'])
            ->addColumn('p2_level','biginteger',['limit' => 20, 'default' => -1, 'null' => true, 'comment' => '上2级等级'])
            ->addColumn('p3_level','biginteger',['limit' => 20, 'default' => -1, 'null' => true, 'comment' => '上3级等级'])
            ->addColumn('p0_reward_type','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '会员计算类型(0固定金额,1交易比例,2利润比例)'])
            ->addColumn('p0_reward_number','decimal',['precision' => 20, 'scale' => 6, 'default' => '0.000000', 'null' => true, 'comment' => '会员计算系数'])
            ->addColumn('p1_reward_type','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '上1级计算类型(0固定金额,1交易比例,2利润比例)'])
            ->addColumn('p1_reward_number','decimal',['precision' => 20, 'scale' => 6, 'default' => '0.000000', 'null' => true, 'comment' => '上1级计算系数'])
            ->addColumn('p2_reward_type','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '上2级计算类型(0固定金额,1交易比例,2利润比例)'])
            ->addColumn('p2_reward_number','decimal',['precision' => 20, 'scale' => 6, 'default' => '0.000000', 'null' => true, 'comment' => '上2级计算系数'])
            ->addColumn('p3_reward_type','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '上3级计算类型(0固定金额,1交易比例,2利润比例)'])
            ->addColumn('p3_reward_number','decimal',['precision' => 20, 'scale' => 6, 'default' => '0.000000', 'null' => true, 'comment' => '上3级计算系数'])
            ->addColumn('remark','string',['limit' => 200, 'default' => '', 'null' => true, 'comment' => '配置描述'])
            ->addColumn('sort','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
            ->addColumn('status','integer',['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '激活状态(0无效,1有效)'])
            ->addColumn('deleted','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(1已删,0未删)'])
            ->addColumn('create_time','datetime',['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time','datetime',['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('code', ['name' => 'iba136ef38_code'])
            ->addIndex('sort', ['name' => 'iba136ef38_sort'])
            ->addIndex('name', ['name' => 'iba136ef38_name'])
            ->addIndex('type', ['name' => 'iba136ef38_type'])
            ->addIndex('stype', ['name' => 'iba136ef38_stype'])
            ->addIndex('status', ['name' => 'iba136ef38_status'])
            ->addIndex('deleted', ['name' => 'iba136ef38_deleted'])
            ->addIndex('p1_level', ['name' => 'iba136ef38_p1_level'])
            ->addIndex('p2_level', ['name' => 'iba136ef38_p2_level'])
            ->addIndex('p3_level', ['name' => 'iba136ef38_p3_level'])
            ->addIndex('p0_level', ['name' => 'iba136ef38_p0_level'])
            ->addIndex('create_time', ['name' => 'iba136ef38_create_time'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class ShopConfigLevel
     * @table shop_config_level
     * @return void
     */
    private function _create_shop_config_level() {

        // 当前数据表
        $table = 'shop_config_level';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-配置-等级',
        ])
            ->addColumn('name','string',['limit' => 180, 'default' => '', 'null' => true, 'comment' => '用户级别名称'])
            ->addColumn('cover','string',['limit' => 500, 'default' => '', 'null' => true, 'comment' => '用户等级图标'])
            ->addColumn('cardbg','string',['limit' => 500, 'default' => '', 'null' => true, 'comment' => '用户等级卡片'])
            ->addColumn('number','integer',['limit' => 2, 'default' => 0, 'null' => true, 'comment' => '用户级别序号'])
            ->addColumn('upgrade_type','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '会员升级规则(0单个,1同时)'])
            ->addColumn('upgrade_team','integer',['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '团队人数统计(0不计,1累计)'])
            ->addColumn('enter_vip_status','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '入会礼包状态'])
            ->addColumn('order_amount_status','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '订单金额状态'])
            ->addColumn('order_amount_number','decimal',['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '订单金额累计'])
            ->addColumn('teams_users_status','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '团队人数状态'])
            ->addColumn('teams_users_number','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '团队人数累计'])
            ->addColumn('teams_direct_status','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '直推人数状态'])
            ->addColumn('teams_direct_number','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '直推人数累计'])
            ->addColumn('teams_indirect_status','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '间推人数状态'])
            ->addColumn('teams_indirect_number','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '间推人数累计'])
            ->addColumn('remark','string',['limit' => 500, 'default' => '', 'null' => true, 'comment' => '用户级别描述'])
            ->addColumn('utime','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '等级更新时间'])
            ->addColumn('status','integer',['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '用户等级状态(1使用,0禁用)'])
            ->addColumn('create_time','datetime',['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time','datetime',['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('utime', ['name' => 'idx_shop_config_level_utime'])
            ->addIndex('status', ['name' => 'idx_shop_config_level_status'])
            ->addIndex('number', ['name' => 'idx_shop_config_level_number'])
            ->addIndex('create_time', ['name' => 'idx_shop_config_level_create_time'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class ShopUserRebate
     * @table shop_user_rebate
     * @return void
     */
    private function _create_shop_user_rebate() {

        // 当前数据表
        $table = 'shop_user_rebate';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-用户-返佣',
        ])
            ->addColumn('unid','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '用户UNID'])
            ->addColumn('date','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '奖励日期'])
            ->addColumn('code','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '奖励编号'])
            ->addColumn('type','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '奖励类型'])
            ->addColumn('name','string',['limit' => 100, 'default' => '', 'null' => true, 'comment' => '奖励名称'])
            ->addColumn('amount','decimal',['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '奖励数量'])
            ->addColumn('order_no','string',['limit' => 20, 'default' => '', 'null' => true, 'comment' => '订单单号'])
            ->addColumn('order_unid','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '订单用户'])
            ->addColumn('order_amount','decimal',['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '订单金额'])
            ->addColumn('remark','string',['limit' => 50, 'default' => '', 'null' => true, 'comment' => '奖励描述'])
            ->addColumn('status','integer',['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '生效状态(0未生效,1已生效)'])
            ->addColumn('deleted','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删除,1已删除)'])
            ->addColumn('create_time','datetime',['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time','datetime',['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('type', ['name' => 'idx_shop_user_rebate_type'])
            ->addIndex('date', ['name' => 'idx_shop_user_rebate_date'])
            ->addIndex('code', ['name' => 'idx_shop_user_rebate_code'])
            ->addIndex('name', ['name' => 'idx_shop_user_rebate_name'])
            ->addIndex('status', ['name' => 'idx_shop_user_rebate_status'])
            ->addIndex('unid', ['name' => 'idx_shop_user_rebate_unid'])
            ->addIndex('deleted', ['name' => 'idx_shop_user_rebate_deleted'])
            ->addIndex('create_time', ['name' => 'idx_shop_user_rebate_create_time'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }
}
