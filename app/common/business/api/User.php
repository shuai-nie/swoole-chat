<?php

namespace app\common\business\api;

use app\common\model\api\Friend;
use app\common\model\api\User as UserModel;
use Exception;
use server\lib\Redis;
use server\lib\Str;
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

    public function addFriend($data)
    {
        $isExist = $this->userModel->findByUserNameWithStatus($data['username']);
        if (empty($isExist)) {
            throw new Exception('用户不存在');
        }

        $socket = $this->redis->get(config('redis.socket_pre').$isExist['id']);
        if (!empty($socket['apply_list'])) {
            foreach ($socket['apply_list'] as $key => $value ) {
                if ($key == $data['uid']) {
                    throw new Exception('请勿重复添加');
                }
            }
        }

        if ($this->friendModel->isFriend($data['user']['id'], $isExist['id'])) {
            throw new Exception('已经是好友');
        }


        if ($isExist['id'] == $data['user']['id']) {
            throw new Exception('不能添加自己为好友');
        }

        $send = [
            'type' => 'add_friend',
            'uid' => $data['user']['id'],
            'username' => $data['user']['username'],
            'target' => $isExist['id'],
            'target_username' => $isExist['username'],
        ];

        $client = new Client('wss://apptest.huihuagongxue.top:9502?type=public&token=' . $data['token']);
        $client -> send(json_encode($send));
        $receive = json_decode($client -> receive(), true);
        if ($receive['status'] == config('status.success')){
            $client -> close();
        }
    }

    public function logoff($token)
    {
        $this->redis->delete(config('redis.socket_pre').$token);
    }

    public function login($data)
    {
        $isExist = $this->userModel->findByUserNameWithStatus($data['username']);
        if (empty($isExist)) {
            throw new Exception('用户不存在');
        }
        $password = md5($isExist['password_salt'] . $data['password']. $isExist['last_login_token']);
        if ($password != $isExist['password']) {
            throw new Exception('密码错误');
        }

        $this->redis->delete(config('redis.socket_pre').$isExist['last_login_token']);
        $token = $this->str->createToken($isExist['username']);
        $this->userModel->updateLoginInfo([
            'username' => $isExist['username'],
            'last_login_token' => $token,
        ]);
        $this->redis->set(config('redis.socket_pre').$token, [
            'id' => $isExist['id'],
            'username' => $isExist['username'],
        ]);
        return $token;
    }

    public function register($data)
    {
        $isExist = $this->userModel->findByUserName($data['username']);
        if (!empty($isExist)) {
            throw new Exception('用户已存在');
        }
        $data['password_salt'] = $this->str->salt(5);
        $data['password'] = md5($data['password_salt'] . $data['password']);
        $this->userModel->save($data);
    }

}