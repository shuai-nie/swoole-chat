<?php

namespace app\api\controller;

use app\BaseController;

use app\common\business\api\User as UserBusiness;
use app\common\validate\api\User as UserValidate;
use think\App;
use think\Validate;

class User extends BaseController
{
    protected $business = null;
    public function  __construct(App $app)
    {
        parent::__construct($app);
        $this->business = new UserBusiness();
    }

    public function friendList()
    {
        $list = $this->business->friendList($this->getUid());
        return $this->success($list);
    }

    public function handleFriend()
    {
        $data['decision'] = $this->request->param('decision', '', "htmlspecialchars");
        $data['target'] = $this->request->param('target', '', "htmlspecialchars");
        $data['uid'] = $this->getUid();
        try {
            validate(UserValidate::class)->scene("handleFriend")->check($data);
        }catch (\Exception $exception) {
            return $this->error($exception->getMessage());
        }
        $this->business->addFriend($data);
        return $this->success("好友申请处理完成");
    }

    public function addFriend()
    {
        $data['username'] = $this->request->param('username', '', "htmlspecialchars");
        $data["message"] = $this->request->param('message', '', "htmlspecialchars");
        $data['user'] = $this->getUid();
        $data['token'] = $this->getToken();
        try {
            validate(UserValidate::class)->scene("addFriend")->check($data);
        }catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
        $this->business->addFriend($data);
        return $this->success("好友申请发送成功");
    }

    public function isLogin()
    {
        return $this->success("token验证成功");
    }

    public function logoff()
    {
        $this->business->logoff($this->getToken());
        return $this->success("注销成功");
    }

    public function login()
    {
        $data['username'] = $this->request->param('username', '', "htmlspecialchars");
        $data['password'] = $this->request->param('password', '', "htmlspecialchars");
        try {
            validate(UserValidate::class)->scene("login")->check($data);
        }catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
        $errCode = $this->business->login($data);
        return $this->success($errCode);
    }

    public function register()
    {
        $data['username'] = $this->request->param('username', '', "htmlspecialchars");
        $data['password'] = $this->request->param('password', '', "htmlspecialchars");
        try {
            validate(UserValidate::class)->scene("register")->check($data);
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
        $this->business->register($data);
        return $this->success("注册成功");
    }





}