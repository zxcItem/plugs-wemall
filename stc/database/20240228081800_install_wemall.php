<?php

use think\migration\Migrator;

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
        $this->_create_plugin_wemall_config_agent();
        $this->_create_plugin_wemall_config_discount();
        $this->_create_plugin_wemall_config_level();
        $this->_create_plugin_wemall_config_rebate();
        $this->_create_plugin_wemall_user_create();
        $this->_create_plugin_wemall_user_rebate();
        $this->_create_plugin_wemall_user_recharge();
        $this->_create_plugin_wemall_user_relation();
    }

    /**
     * 创建数据对象
     * @class PluginWemallConfigAgent
     * @table plugin_wemall_config_agent
     * @return void
     */
    private function _create_plugin_wemall_config_agent()
    {

        // 当前数据表
        $table = 'plugin_wemall_config_agent';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-配置-等级',
        ])
            ->addColumn('name', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '级别名称'])
            ->addColumn('cover', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '等级图标'])
            ->addColumn('cardbg', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '等级卡片'])
            ->addColumn('number', 'integer', ['limit' => 2, 'default' => 0, 'null' => true, 'comment' => '级别序号'])
            ->addColumn('upgrade_type', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '升级规则(0单个,1同时)'])
            ->addColumn('extra', 'text', ['default' => NULL, 'null' => true, 'comment' => '升级规则'])
            ->addColumn('remark', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '级别描述'])
            ->addColumn('utime', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '更新时间'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '等级状态(1使用,0禁用)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('utime', ['name' => 'i6a80c4b9e_utime'])
            ->addIndex('status', ['name' => 'i6a80c4b9e_status'])
            ->addIndex('number', ['name' => 'i6a80c4b9e_number'])
            ->addIndex('create_time', ['name' => 'i6a80c4b9e_create_time'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallConfigDiscount
     * @table plugin_wemall_config_discount
     * @return void
     */
    private function _create_plugin_wemall_config_discount()
    {

        // 当前数据表
        $table = 'plugin_wemall_config_discount';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-配置-折扣',
        ])
            ->addColumn('name', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '方案名称'])
            ->addColumn('remark', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '方案描述'])
            ->addColumn('items', 'text', ['default' => NULL, 'null' => true, 'comment' => '方案规则'])
            ->addColumn('sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '方案状态(0禁用,1使用)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(1已删,0未删)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('sort', ['name' => 'i8d0e0158e_sort'])
            ->addIndex('status', ['name' => 'i8d0e0158e_status'])
            ->addIndex('deleted', ['name' => 'i8d0e0158e_deleted'])
            ->addIndex('create_time', ['name' => 'i8d0e0158e_create_time'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallConfigLevel
     * @table plugin_wemall_config_level
     * @return void
     */
    private function _create_plugin_wemall_config_level()
    {

        // 当前数据表
        $table = 'plugin_wemall_config_level';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-配置-等级',
        ])
            ->addColumn('name', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '级别名称'])
            ->addColumn('cover', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '等级图标'])
            ->addColumn('cardbg', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '等级卡片'])
            ->addColumn('number', 'integer', ['limit' => 2, 'default' => 0, 'null' => true, 'comment' => '级别序号'])
            ->addColumn('upgrade_type', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '升级规则(0单个,1同时)'])
            ->addColumn('upgrade_team', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '团队人数统计(0不计,1累计)'])
            ->addColumn('remark', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '用户级别描述'])
            ->addColumn('extra', 'text', ['default' => NULL, 'null' => true, 'comment' => '配置规则'])
            ->addColumn('utime', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '更新时间'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '等级状态(1使用,0禁用)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('utime', ['name' => 'if851bb0b1_utime'])
            ->addIndex('status', ['name' => 'if851bb0b1_status'])
            ->addIndex('number', ['name' => 'if851bb0b1_number'])
            ->addIndex('create_time', ['name' => 'if851bb0b1_create_time'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallConfigRebate
     * @table plugin_wemall_config_rebate
     * @return void
     */
    private function _create_plugin_wemall_config_rebate()
    {

        // 当前数据表
        $table = 'plugin_wemall_config_rebate';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-配置-返利',
        ])
            ->addColumn('type', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '奖励类型'])
            ->addColumn('code', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '配置编号'])
            ->addColumn('name', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '配置名称'])
            ->addColumn('path', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '等级关系'])
            ->addColumn('stype', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '结算类型(0支付结算,1收货结算)'])
            ->addColumn('p0_level', 'biginteger', ['limit' => 20, 'default' => -1, 'null' => true, 'comment' => '会员等级'])
            ->addColumn('p1_level', 'biginteger', ['limit' => 20, 'default' => -1, 'null' => true, 'comment' => '上1级等级'])
            ->addColumn('p2_level', 'biginteger', ['limit' => 20, 'default' => -1, 'null' => true, 'comment' => '上2级等级'])
            ->addColumn('p3_level', 'biginteger', ['limit' => 20, 'default' => -1, 'null' => true, 'comment' => '上3级等级'])
            ->addColumn('p0_reward_type', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '会员计算类型(0固定金额,1交易比例,2利润比例)'])
            ->addColumn('p0_reward_number', 'decimal', ['precision' => 20, 'scale' => 6, 'default' => '0.000000', 'null' => true, 'comment' => '会员计算系数'])
            ->addColumn('p1_reward_type', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '上1级计算类型(0固定金额,1交易比例,2利润比例)'])
            ->addColumn('p1_reward_number', 'decimal', ['precision' => 20, 'scale' => 6, 'default' => '0.000000', 'null' => true, 'comment' => '上1级计算系数'])
            ->addColumn('p2_reward_type', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '上2级计算类型(0固定金额,1交易比例,2利润比例)'])
            ->addColumn('p2_reward_number', 'decimal', ['precision' => 20, 'scale' => 6, 'default' => '0.000000', 'null' => true, 'comment' => '上2级计算系数'])
            ->addColumn('p3_reward_type', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '上3级计算类型(0固定金额,1交易比例,2利润比例)'])
            ->addColumn('p3_reward_number', 'decimal', ['precision' => 20, 'scale' => 6, 'default' => '0.000000', 'null' => true, 'comment' => '上3级计算系数'])
            ->addColumn('remark', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '配置描述'])
            ->addColumn('sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '激活状态(0无效,1有效)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(1已删,0未删)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('code', ['name' => 'i3a0d023e7_code'])
            ->addIndex('sort', ['name' => 'i3a0d023e7_sort'])
            ->addIndex('name', ['name' => 'i3a0d023e7_name'])
            ->addIndex('type', ['name' => 'i3a0d023e7_type'])
            ->addIndex('stype', ['name' => 'i3a0d023e7_stype'])
            ->addIndex('status', ['name' => 'i3a0d023e7_status'])
            ->addIndex('deleted', ['name' => 'i3a0d023e7_deleted'])
            ->addIndex('p1_level', ['name' => 'i3a0d023e7_p1_level'])
            ->addIndex('p2_level', ['name' => 'i3a0d023e7_p2_level'])
            ->addIndex('p3_level', ['name' => 'i3a0d023e7_p3_level'])
            ->addIndex('p0_level', ['name' => 'i3a0d023e7_p0_level'])
            ->addIndex('create_time', ['name' => 'i3a0d023e7_create_time'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }


    /**
     * 创建数据对象
     * @class PluginWemallUserCreate
     * @table plugin_wemall_user_create
     * @return void
     */
    private function _create_plugin_wemall_user_create()
    {

        // 当前数据表
        $table = 'plugin_wemall_user_create';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-用户-创建',
        ])
            ->addColumn('unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => false, 'comment' => '关联用户'])
            ->addColumn('name', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '用户姓名'])
            ->addColumn('phone', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '手机号码'])
            ->addColumn('headimg', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '用户头像'])
            ->addColumn('password', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '初始密码'])
            ->addColumn('rebate_total', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '累计返利'])
            ->addColumn('rebate_total_code', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '记录编号'])
            ->addColumn('rebate_total_desc', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '记录描述'])
            ->addColumn('rebate_usable', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '可提返利'])
            ->addColumn('rebate_usable_code', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '记录编号'])
            ->addColumn('rebate_usable_desc', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '记录描述'])
            ->addColumn('agent_entry', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '代理权限'])
            ->addColumn('agent_phone', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '上级手机'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '记录状态(0无效,1有效)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('name', ['name' => 'i55481d44e_name'])
            ->addIndex('unid', ['name' => 'i55481d44e_unid'])
            ->addIndex('phone', ['name' => 'i55481d44e_phone'])
            ->addIndex('status', ['name' => 'i55481d44e_status'])
            ->addIndex('deleted', ['name' => 'i55481d44e_deleted'])
            ->addIndex('create_time', ['name' => 'i55481d44e_create_time'])
            ->addIndex('agent_entry', ['name' => 'i55481d44e_agent_entry'])
            ->addIndex('agent_phone', ['name' => 'i55481d44e_agent_phone'])
            ->addIndex('rebate_total', ['name' => 'i55481d44e_rebate_total'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallUserRebate
     * @table plugin_wemall_user_rebate
     * @return void
     */
    private function _create_plugin_wemall_user_rebate()
    {

        // 当前数据表
        $table = 'plugin_wemall_user_rebate';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-用户-返利',
        ])
            ->addColumn('unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '用户UNID'])
            ->addColumn('layer', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '上级层级'])
            ->addColumn('code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '奖励编号'])
            ->addColumn('hash', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '维一编号'])
            ->addColumn('date', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '奖励日期'])
            ->addColumn('type', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '奖励类型'])
            ->addColumn('name', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '奖励名称'])
            ->addColumn('amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '奖励数量'])
            ->addColumn('order_no', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '订单单号'])
            ->addColumn('order_unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '订单用户'])
            ->addColumn('order_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '订单金额'])
            ->addColumn('remark', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '奖励描述'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '生效状态(0未生效,1已生效)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删除,1已删除)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addColumn('confirm_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '到账时间'])
            ->addIndex('type', ['name' => 'i5f9c1d4b3_type'])
            ->addIndex('date', ['name' => 'i5f9c1d4b3_date'])
            ->addIndex('code', ['name' => 'i5f9c1d4b3_code'])
            ->addIndex('name', ['name' => 'i5f9c1d4b3_name'])
            ->addIndex('unid', ['name' => 'i5f9c1d4b3_unid'])
            ->addIndex('hash', ['name' => 'i5f9c1d4b3_hash'])
            ->addIndex('status', ['name' => 'i5f9c1d4b3_status'])
            ->addIndex('deleted', ['name' => 'i5f9c1d4b3_deleted'])
            ->addIndex('order_no', ['name' => 'i5f9c1d4b3_order_no'])
            ->addIndex('order_unid', ['name' => 'i5f9c1d4b3_order_unid'])
            ->addIndex('create_time', ['name' => 'i5f9c1d4b3_create_time'])
            ->addIndex('confirm_time', ['name' => 'i5f9c1d4b3_confirm_time'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallUserRecharge
     * @table plugin_wemall_user_recharge
     * @return void
     */
    private function _create_plugin_wemall_user_recharge()
    {

        // 当前数据表
        $table = 'plugin_wemall_user_recharge';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-用户-充值',
        ])
            ->addColumn('unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '账号编号'])
            ->addColumn('code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '操作编号'])
            ->addColumn('name', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '操作名称'])
            ->addColumn('remark', 'string', ['limit' => 999, 'default' => '', 'null' => true, 'comment' => '操作备注'])
            ->addColumn('amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '操作金额'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删除,1已删除)'])
            ->addColumn('create_by', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '系统用户'])
            ->addColumn('deleted_by', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '系统用户'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('deleted_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '删除时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('unid', ['name' => 'id6918c8c5_unid'])
            ->addIndex('code', ['name' => 'id6918c8c5_code'])
            ->addIndex('deleted', ['name' => 'id6918c8c5_deleted'])
            ->addIndex('create_time', ['name' => 'id6918c8c5_create_time'])
            ->addIndex('deleted_time', ['name' => 'id6918c8c5_deleted_time'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class PluginWemallUserRelation
     * @table plugin_wemall_user_relation
     * @return void
     */
    private function _create_plugin_wemall_user_relation()
    {

        // 当前数据表
        $table = 'plugin_wemall_user_relation';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商城-用户-关系',
        ])
            ->addColumn('unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '当前用户'])
            ->addColumn('puids', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '绑定状态'])
            ->addColumn('puid1', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '上1级代理'])
            ->addColumn('puid2', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '上2级代理'])
            ->addColumn('puid3', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '上3级代理'])
            ->addColumn('layer', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '所属层级'])
            ->addColumn('path', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '关系路径'])
            ->addColumn('extra', 'text', ['default' => NULL, 'null' => true, 'comment' => '扩展数据'])
            ->addColumn('entry_agent', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '推广权益(0无,1有)'])
            ->addColumn('entry_member', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '入会礼包(0无,1有)'])
            ->addColumn('level_code', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '会员等级'])
            ->addColumn('level_name', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '会员名称'])
            ->addColumn('agent_uuid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '绑定用户'])
            ->addColumn('agent_state', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '绑定状态'])
            ->addColumn('agent_level_code', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '代理等级'])
            ->addColumn('agent_level_name', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '代理名称'])
            ->addColumn('sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('unid', ['name' => 'i863175e04_unid'])
            ->addIndex('path', ['name' => 'i863175e04_path'])
            ->addIndex('puid1', ['name' => 'i863175e04_puid1'])
            ->addIndex('puid2', ['name' => 'i863175e04_puid2'])
            ->addIndex('puid3', ['name' => 'i863175e04_puid3'])
            ->addIndex('level_code', ['name' => 'i863175e04_level_code'])
            ->addIndex('agent_uuid', ['name' => 'i863175e04_agent_uuid'])
            ->addIndex('create_time', ['name' => 'i863175e04_create_time'])
            ->addIndex('entry_agent', ['name' => 'i863175e04_entry_agent'])
            ->addIndex('entry_member', ['name' => 'i863175e04_entry_member'])
            ->addIndex('agent_level_code', ['name' => 'i863175e04_agent_level_code'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

}
