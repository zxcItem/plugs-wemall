<?php

declare (strict_types=1);

namespace plugin\wemall\command;

use plugin\account\model\AccountUser;
use plugin\wemall\service\UserUpgrade;
use think\admin\Command;
use think\admin\Exception;
use think\console\Input;
use think\console\Output;
use think\db\exception\DbException;

/**
 * 同步计算用户信息
 * @class Users
 * @package plugin\wemall\command
 */
class Users extends Command
{

    public function configure()
    {
        $this->setName('xdata:mall:users')->setDescription('同步用户关联数据');
    }

    /**
     * 执行指令
     * @param Input $input
     * @param Output $output
     * @throws Exception
     * @throws DbException
     */
    protected function execute(Input $input, Output $output)
    {
        [$total, $count] = [AccountUser::mk()->count(), 0];
        foreach (AccountUser::mk()->field('id')->order('id desc')->cursor() as $user) try {
            $this->queue->message($total, ++$count, "刷新用户 [{$user['id']}] 数据...");
            UserUpgrade::recount(intval($user['id']), true);
            UserUpgrade::upgrade(intval($user['id']));
            $this->queue->message($total, $count, "刷新用户 [{$user['id']}] 数据成功", 1);
        } catch (\Exception $exception) {
            $this->queue->message($total, $count, "刷新用户 [{$user['id']}] 数据失败, {$exception->getMessage()}", 1);
        }
        $this->setQueueSuccess("此次共处理 {$total} 个刷新操作。");
    }
}