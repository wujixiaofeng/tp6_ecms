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

namespace think\facade;

use think\Facade;

/**
 * @see \think\Log
 * @mixin \think\Log
 * @method \think\Log init(array $config = []) static ��־��ʼ��
 * @method mixed getLog(string $type = '') static ��ȡ��־��Ϣ
 * @method \think\Log record(mixed $msg, string $type = 'info', array $context = []) static ��¼��־��Ϣ
 * @method \think\Log clear() static �����־��Ϣ
 * @method \think\Log key(string $key) static ��ǰ��־��¼����Ȩkey
 * @method \think\Log close() static �رձ���������־д��
 * @method bool check(array $config) static �����־д��Ȩ��
 * @method bool save() static ���������Ϣ
 * @method void write(mixed $msg, string $type = 'info', bool $force = false) static ʵʱд����־��Ϣ
 * @method void log(string $level,mixed $message, array $context = []) static ��¼��־��Ϣ
 * @method void emergency(mixed $message, array $context = []) static ��¼emergency��Ϣ
 * @method void alert(mixed $message, array $context = []) static ��¼alert��Ϣ
 * @method void critical(mixed $message, array $context = []) static ��¼critical��Ϣ
 * @method void error(mixed $message, array $context = []) static ��¼error��Ϣ
 * @method void warning(mixed $message, array $context = []) static ��¼warning��Ϣ
 * @method void notice(mixed $message, array $context = []) static ��¼notice��Ϣ
 * @method void info(mixed $message, array $context = []) static ��¼info��Ϣ
 * @method void debug(mixed $message, array $context = []) static ��¼debug��Ϣ
 * @method void sql(mixed $message, array $context = []) static ��¼sql��Ϣ
 */
class Log extends Facade
{
    /**
     * ��ȡ��ǰFacade��Ӧ�����������Ѿ��󶨵����������ʶ��
     * @access protected
     * @return string
     */
    protected static function getFacadeClass()
    {
        return 'log';
    }
}
