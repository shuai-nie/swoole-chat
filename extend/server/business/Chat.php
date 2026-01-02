<?php

namespace server\business;

use server\base\Base;
use app\common\model\api\Chat as Model;
use app\common\model\api\Friend;

class Chat extends Base
{
    private $chatModel = null;
    private $friendModel = null;

    public function __construct()
    {
        parent::__construct();
        $this->chatModel = new Model();
        $this->friendModel = new Friend();
    }

    public function switchboard($ws, $frame)
    {
        $data = json_decode($frame->data, true);
        switch ($data['type']) {
            case "addFriend":
                $this->addFriend($ws, $frame->fd, $data);
                break;
            case "chat":
                $this->chat($ws, $frame->fd, $data);
                break;
        }
    }

    private function chat($ws, $fd, $data)
    {
        $uid = $this->getBindUid($ws, $fd);
        if(!$this->friendModel->isFriend($uid, $data['uid'])) {
            $this->fail($ws, $fd, '不是好友关系');
            return;
        }
        $this->chatModel->insert([
            'uid' => $uid,
            'fid' => $data['uid'],
            'message' => $data['message'],
            'create_time' => time()
        ]);

        $socket = $this->getSocket($data['uid']);
        if(!empty($socket['fd']['chat_uid_'.$uid])) {
            $this->success($ws, $socket['fd']['chat_uid'. $uid], [
                'message' => $data['message'],
            ]);
        }elseif (!empty($socket['fd']['index'])) {
            $this->success($ws, $socket['fd']['index'], [
                'type' => 'chat',
                'uid' => $uid,
                'message' => $data['message'],
            ]);
        }else {
            if(!empty($socket['delay_list'][$uid])) {
                $socket['delay_list'][$uid]['count'] += 1;
                $socket['delay_list'][$uid]['message'] = $data['message'];
            } else {
                $socket['delay_list'][$uid] = [
                    'count' => 1,
                    'message' => $data['message'],
                ];
            }
            $this->redis->set(config('redis.socket_pre').$data['uid'], $socket);
        }
    }

    private function addFriend($ws, $fd, $data)
    {
        $socket = $this->getSocket($data['target']);
        $socket['apply_list'][$data['uid']] = $data['message'];
        if(!empty($socket['fd']['index'])) {
            $this->success($ws, $socket['fd']['index'], [
                'type' => 'addFriend',
                'uid' => $data['uid'],
                'message' => $data['message'],
                'username' => $data['username'],
            ]);
        }
        $this->redis->set(config('redis.socket_pre').$data['target'], $socket);
        $this->success($ws, $fd, null);
    }

}