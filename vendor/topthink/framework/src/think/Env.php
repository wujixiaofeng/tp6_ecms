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

use ArrayAccess;

/**
 * Env������
 */
class Env implements ArrayAccess
{
    /**
     * ������������
     * @var array
     */
    protected $data = [];

    public function __construct()
    {
        $this->data = $_ENV;
    }

    /**
     * ��ȡ�������������ļ�
     * @access public
     * @param string $file �������������ļ�
     * @return void
     */
    public function load(string $file): void
    {
        $env = parse_ini_file($file, true) ?: [];
        $this->set($env);
    }

    /**
     * ��ȡ��������ֵ
     * @access public
     * @param string $name    ����������
     * @param mixed  $default Ĭ��ֵ
     * @return mixed
     */
    public function get(string $name = null, $default = null)
    {
        if (is_null($name)) {
            return $this->data;
        }

        $name = strtoupper(str_replace('.', '_', $name));

        if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        return $this->getEnv($name, $default);
    }

    protected function getEnv(string $name, $default = null)
    {
        $result = getenv('PHP_' . $name);

        if (false === $result) {
            return $default;
        }

        if ('false' === $result) {
            $result = false;
        } elseif ('true' === $result) {
            $result = true;
        }

        if (!isset($this->data[$name])) {
            $this->data[$name] = $result;
        }

        return $result;
    }

    /**
     * ���û�������ֵ
     * @access public
     * @param string|array $env   ��������
     * @param mixed        $value ֵ
     * @return void
     */
    public function set($env, $value = null): void
    {
        if (is_array($env)) {
            $env = array_change_key_case($env, CASE_UPPER);

            foreach ($env as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $k => $v) {
                        $this->data[$key . '_' . strtoupper($k)] = $v;
                    }
                } else {
                    $this->data[$key] = $val;
                }
            }
        } else {
            $name = strtoupper(str_replace('.', '_', $env));

            $this->data[$name] = $value;
        }
    }

    /**
     * ����Ƿ���ڻ�������
     * @access public
     * @param string $name ������
     * @return bool
     */
    public function has(string $name): bool
    {
        return !is_null($this->get($name));
    }

    /**
     * ���û�������
     * @access public
     * @param string $name  ������
     * @param mixed  $value ֵ
     */
    public function __set(string $name, $value): void
    {
        $this->set($name, $value);
    }

    /**
     * ��ȡ��������
     * @access public
     * @param string $name ������
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->get($name);
    }

    /**
     * ����Ƿ���ڻ�������
     * @access public
     * @param string $name ������
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return $this->has($name);
    }

    // ArrayAccess
    public function offsetSet($name, $value): void
    {
        $this->set($name, $value);
    }

    public function offsetExists($name): bool
    {
        return $this->__isset($name);
    }

    public function offsetUnset($name)
    {
        throw new Exception('not support: unset');
    }

    public function offsetGet($name)
    {
        return $this->get($name);
    }
}
