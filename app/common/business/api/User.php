<?php

namespace app\common\business\api;

use lib\Redis;
use lib\Str;
use app\common\model\api\Friend;
use app\common\model\api\User as UserModel;
use Exception;
use think\facade\Db;
use WebSocket\Client;

class User
{

    private $userModel = null;
    private $friendModel = null;
    private $str = null;
    private $redis = null;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->friendModel = new Friend();
        $this->str = new Str();
        $this->redis = new Redis();
    }

    public function friendList($uid)
    {
        return $this->friendModel->friendList($uid);
    }

    public function handleFriend($data)
    {
        $socket = $this->redis->get(config('redis.socket_pre').$data['uid']);
        if (empty($socket['apply_list']) || !array_key_exists($data['target'], $socket['apply_list']) ) {
            throw new Exception('用户不存在');
        }
        Db::startTrans();
        try {
            // 方法用于启动一个事务块，后续的所有Redis命令都会被放入队列中
            // 直到调用 exec() 或 discard() 方法时，这些命令才会被执行或丢弃
            $this->redis->multi();
            if ((boolean)$data['decision']) {
                $lists = [
                    [
                        'uid' => $data['target'],
                        'fid' => $data['uid'],
                    ], [
                        'uid' => $data['uid'],
                        'fid' => $data['target'],
                    ]
                ];
                $this->friendModel->saveAll($lists);
            }
            unset($socket['apply_list'][$data['target']]);
            // 重置用户WebSocket连接信息
            $this->redis->reset(config('redis.socket_pre'). $data['uid'], $socket);
            // 执行Redis事务中的所有命令
            $this->redis->exec();


            Db::commit();
        } catch (Exception $e) {
            // 丢弃当前事务队列中的所有命令
            $this->redis->discard();

            Db::rollback();
            throw new Exception($e->getMessage());
        }

    }

}