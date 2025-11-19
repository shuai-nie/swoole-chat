<?php
declare (strict_types = 1);

namespace app\common\validate\api;

use think\Validate;

class Chat extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'fid' => ['require', 'number'],
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'record' => ['fid']
    ];
}
