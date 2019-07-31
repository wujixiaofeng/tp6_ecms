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

namespace think;

/**
 * Facade������
 */
class Facade
{
    /**
     * ʼ�մ����µĶ���ʵ��
     * @var bool
     */
    protected static $alwaysNewInstance;

    /**
     * ����Facadeʵ��
     * @static
     * @access protected
     * @param  string $class       �������ʶ
     * @param  array  $args        ����
     * @param  bool   $newInstance �Ƿ�ÿ�δ����µ�ʵ��
     * @return object
     */
    protected static function createFacade(string $class = '', array $args = [], bool $newInstance = false)
    {
        $class = $class ?: static::class;

        $facadeClass = static::getFacadeClass();

        if ($facadeClass) {
            $class = $facadeClass;
        }

        if (static::$alwaysNewInstance) {
            $newInstance = true;
        }

        return Container::getInstance()->make($class, $args, $newInstance);
    }

    /**
     * ��ȡ��ǰFacade��Ӧ����
     * @access protected
     * @return string
     */
    protected static function getFacadeClass()
    {}

    /**
     * ������ʵ������ǰFacade��
     * @access public
     * @param  array $args ����
     * @return object
     */
    public static function instance(...$args)
    {
        if (__CLASS__ != static::class) {
            return self::createFacade('', $args);
        }
    }

    /**
     * �������ʵ��
     * @access public
     * @param  string     $class       �������߱�ʶ
     * @param  array|true $args        ����
     * @param  bool       $newInstance �Ƿ�ÿ�δ����µ�ʵ��
     * @return object
     */
    public static function make(string $class, $args = [], bool $newInstance = false)
    {
        if (__CLASS__ != static::class) {
            return self::__callStatic('make', func_get_args());
        }

        if (true === $args) {
            // ���Ǵ����µ�ʵ��������
            $newInstance = true;
            $args        = [];
        }

        return self::createFacade($class, $args, $newInstance);
    }

    // ����ʵ����ķ���
    public static function __callStatic($method, $params)
    {
        return call_user_func_array([static::createFacade(), $method], $params);
    }
}
