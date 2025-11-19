<?php
declare (strict_types = 1);

namespace app\common\model\api;

use think\Model;

/**
 * @mixin \think\Model
 */
class Chat extends Model
{
    protected $table = 'api_chat';
    public function getRecord($uid, $fid) {
        return $this->field('id, uid, fid, content, time')->where('uid', $uid)->where('fid', $fid)->select();
    }
}
