<?php

namespace server\base;

use app\common\business\lib\Redis;
use app\common\model\api\User;
class Base
{

    protected $redis = null;
    protected $user = null;

    public function __construct()
    {
        $this->redis = new Redis();
        $this->user = new User();
    }

    public function handle($token, $type, $ws, $fd)
    {
        $user = $this->getUser($token);
        if (empty($user)) {
            $ws->clost($fd);
        } else {
            $ws->bind($fd, $user['id']);
            if(strpos($type, 'chat_uid') !== false) {
                $this->setFd($ws, $user['id'], $fd, $type);
            }
            if ($type == 'index') {
                $this->setFd($ws, $user['id'], $fd, $type);
                $this->readDelay($ws, $user['id'], $fd);
            }
        }
    }

    public function readDelay($ws, $uid, $fd)
    {
        $data = $this->getSoclet($uid);
        foreach ($data['apply_list'] as $key => $value) {
            $user = $this->user->findByIdWithStatus($key);
            if (empty($user)) {
                unset($data['apply_list'][$key]);
                continue;
            }
            $this->success($ws, $fd, [
                'type' => 'addFriend',
                'from' => $key,
                'username' => $user['username'],
                'message' => $value
            ]);
        }

        if(!empty($data['apply_list'])) {
            foreach ($data['apply_list'] as $key => $value) {
                $this->success($ws, $fd, [
                    'type' => 'chat',
                    'from' => $key,
                    'count' => $value['count'],
                    'message' => $value['message'],
                ]);
                unset($data['delay_list'][$key]);
            }
        }
        $this->redis->set(config('redis.soclet_key').$uid, $data);
    }

    public function setFd($ws, $uid, $fd, $type)
    {
        $data = $this->getSoclet($uid);
        $data['fd'][$type] = $fd;
        foreach ($data['fd'] as $key => $value) {
            $bindUid = $this->getBindUid($ws, $value);
            if(empty($bindUid) || $bindUid != $uid ) {
                unset($data['fd'][$key]);
            }
        }
        $this->redis->set(config('redis.soclet_key').$uid, $data);
    }

    public function getBindUid($ws, $fd)
    {
        $info = $ws->getClientInfo($fd);
        return empty($info['uid']) ? null : $info['uid'];
    }

    public function getSocket($uid)
    {
        return $this->redis->get(config('redis.soclet_key').$uid);
    }

    public function getUser($token)
    {
        return $this->user->get(config('redis.token_pre'). $token);
    }

    public function success($ws, $fd, $data)
    {
        $this->show($ws, $fd, config('status.success'), config('message.success'), $data);
    }

    public function fail($ws, $fd, $data)
    {
        $this->show($ws, $fd, config('status.fail'), $data, null);
    }

    public function show($ws, $fd, $status, $message, $data)
    {
        $data = [
            'status' => $status,
            'message' => $message,
            'data' => $data
        ];
        $ws->push($fd, json_encode($data));
    }


}