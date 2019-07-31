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

namespace think\facade;

use think\Facade;

/**
 * @see \think\App
 * @mixin \think\App
 * @method \think\App debug(bool $debug) static ����Ӧ�õ���ģʽ
 * @method \think\App setNamespace(string $namespace) static ����Ӧ�õ������ռ�
 * @method void initialize() static ��ʼ��Ӧ��
 * @method string parseClass(string $layer, string $name) static ����Ӧ���������
 * @method string version() static ��ȡ��ܰ汾
 * @method bool isDebug() static �Ƿ�Ϊ����ģʽ
 * @method bool runningInConsole() static �Ƿ�������CLIģʽ
 * @method string getRootPath() static ��ȡӦ�ø�Ŀ¼
 * @method string getBasePath() static ��ȡӦ�û���Ŀ¼
 * @method string getAppPath() static ��ȡӦ�����Ŀ¼
 * @method string getRuntimePath() static ��ȡӦ������ʱĿ¼
 * @method string getThinkPath() static ��ȡ���Ŀ��Ŀ¼
 * @method string getConfigPath() static ��ȡӦ������Ŀ¼
 * @method string getConfigExt() static ��ȡ���ú�׺
 * @method string getNamespace() static ��ȡӦ����������ռ�
 * @method float getBeginTime() static ��ȡӦ�ÿ���ʱ��
 * @method integer getBeginMem() static ��ȡӦ�ó�ʼ�ڴ�ռ��
 * @method string serialize(mixed $data) static ���л�����
 * @method mixed unserialize(string $data) static �����л�
 * @method string classBaseName(mixed $class) static ��ȡ����(�����������ռ�)
 * @method mixed factory(string $name, string $namespace = '', ...$args) static ��������
 * @method string parseName(string $name = null, int $type = 0, bool $ucfirst = true) static �ַ����������ת��
 */
class App extends Facade
{
    /**
     * ��ȡ��ǰFacade��Ӧ�����������Ѿ��󶨵����������ʶ��
     * @access protected
     * @return string
     */
    protected static function getFacadeClass()
    {
        return 'app';
    }
}
