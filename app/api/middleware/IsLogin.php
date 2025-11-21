<?php
declare (strict_types = 1);

namespace app\api\middleware;

use app\BaseController;

class IsLogin extends BaseController
{
    /**
     * 处理请求
     *
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        $token = $this->getToken();
        if (empty($token)) {
            return $this->show(
                config('status.goto'),
                config('message.goto'),
                ''
            );
        }
    }
}
