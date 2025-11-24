<?php

namespace app\api\controller;

use app\BaseController;
use think\App;
use app\common\business\api\Chat as ChatBusiness;
use app\common\validate\api\Chat as ChatValidate;

class Chat extends BaseController
{
    protected $business = null;
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->business = new ChatBusiness();
    }

    public function record()
    {
        $data['fid'] = $this->request->param('fid', 0, 'htmlspecialchars');
        $data['uid'] = $this->getUid();
        try {
            validate(ChatValidate::class)->scene("record")->check($data);
        }catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
        $record = $this->business->record($data);
        return $this->success($record);
    }



}