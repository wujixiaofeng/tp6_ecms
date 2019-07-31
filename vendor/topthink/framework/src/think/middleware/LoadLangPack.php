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
use think\App;
use think\Lang;
use think\Request;

/**
 * �����Լ���
 */
class LoadLangPack
{

    /**
     * ·�ɳ�ʼ����·�ɹ���ע�ᣩ
     * @access public
     * @param Request $request
     * @param Closure $next
     * @param Lang    $lang
     * @param App     $app
     * @return Response
     */
    public function handle($request, Closure $next, Lang $lang, App $app)
    {
        // �Զ���⵱ǰ����
        $langset = $lang->detect();

        if ($lang->defaultLangSet() != $langset) {
            // ����ϵͳ���԰�
            $lang->load([
                $app->getThinkPath() . 'lang' . DIRECTORY_SEPARATOR . $langset . '.php',
            ]);

            $app->LoadLangPack($langset);
        }

        $lang->saveToCookie($app->cookie);

        return $next($request);
    }
}
