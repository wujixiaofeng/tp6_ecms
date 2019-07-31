<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace think\middleware;

use Closure;
use think\exception\ValidateException;
use think\Request;
use think\Response;

/**
 * ������֧��
 */
class FormTokenCheck
{

    /**
     * �����Ƽ��
     * @access public
     * @param Request $request
     * @param Closure $next
     * @param string  $token ������Token����
     * @return Response
     */
    public function handle(Request $request, Closure $next, string $token = '')
    {
        $check = $request->checkToken($token ?: '__token__');

        if (false === $check) {
            throw new ValidateException('invalid token');
        }

        return $next($request);
    }

}
