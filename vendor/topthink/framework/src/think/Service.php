<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace think;

/**
 * ϵͳ���������
 */
abstract class Service
{
    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * ����·��
     * @access protected
     * @param string $path ·��·��
     */
    protected function loadRoutesFrom($path)
    {
        require $path;
    }

    /**
     * ���ָ��
     * @access protected
     * @param array|string $commands ָ��
     */
    protected function commands($commands)
    {
        $commands = is_array($commands) ? $commands : func_get_args();

        Console::starting(function (Console $console) use ($commands) {
            $console->addCommands($commands);
        });
    }
}
