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
 * ��ͼ��
 */
class View
{
    /**
     * ģ������ʵ��
     * @var object
     */
    public $engine;

    /**
     * ģ�����
     * @var array
     */
    protected $data = [];

    /**
     * ���ݹ���
     * @var mixed
     */
    protected $filter;

    /**
     * ��ʼ��
     * @access public
     * @param  array $options  ģ���������
     * @return $this
     */
    public function __construct(array $options = [])
    {
        // ��ʼ��ģ������
        $type = $options['type'] ?? 'php';
        unset($options['type']);
        $this->engine($type, $options);

        return $this;
    }

    public static function __make(Config $config)
    {
        return new static($config->get('template'));
    }

    /**
     * ģ�������ֵ
     * @access public
     * @param  string|array $name  ģ�����
     * @param  mixed        $value ����ֵ
     * @return $this
     */
    public function assign($name, $value = null)
    {
        if (is_array($name)) {
            $this->data = array_merge($this->data, $name);
        } else {
            $this->data[$name] = $value;
        }

        return $this;
    }

    /**
     * ���õ�ǰģ�����������
     * @access public
     * @param  string       $type    ģ����������
     * @param  array|string $options ģ���������
     * @return $this
     */
    public function engine(string $type, array $options = [])
    {
        $this->engine = App::factory($type, '\\think\\view\\driver\\', $options);

        return $this;
    }

    /**
     * ����ģ������
     * @access public
     * @param  array  $name ģ�����
     * @return $this
     */
    public function config(array $config)
    {
        $this->engine->config($config);

        return $this;
    }

    /**
     * ���ģ���Ƿ����
     * @access public
     * @param  string  $name ������
     * @return bool
     */
    public function exists(string $name): bool
    {
        return $this->engine->exists($name);
    }

    /**
     * ��ͼ����
     * @access public
     * @param Callable  $filter ���˷�����հ�
     * @return $this
     */
    public function filter(callable $filter = null)
    {
        $this->filter = $filter;
        return $this;
    }

    /**
     * �����ͻ�ȡģ������ �������
     * @access public
     * @param  string    $template ģ���ļ�����������
     * @param  bool      $renderContent     �Ƿ���Ⱦ����
     * @return string
     * @throws \Exception
     */
    public function fetch(string $template = '', bool $renderContent = false): string
    {
        // ҳ�滺��
        ob_start();
        ob_implicit_flush(0);

        // ��Ⱦ���
        try {
            $method = $renderContent ? 'display' : 'fetch';
            $this->engine->$method($template, $this->data);
        } catch (\Exception $e) {
            ob_end_clean();
            throw $e;
        }

        // ��ȡ����ջ���
        $content = ob_get_clean();

        if ($this->filter) {
            $content = call_user_func_array($this->filter, [$content]);
        }

        return $content;
    }

    /**
     * ��Ⱦ�������
     * @access public
     * @param  string $content ����
     * @return string
     */
    public function display(string $content): string
    {
        return $this->fetch($content, true);
    }

    /**
     * ģ�������ֵ
     * @access public
     * @param  string    $name  ������
     * @param  mixed     $value ����ֵ
     */
    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * ȡ��ģ����ʾ������ֵ
     * @access protected
     * @param  string $name ģ�����
     * @return mixed
     */
    public function __get($name)
    {
        return $this->data[$name];
    }

    /**
     * ���ģ������Ƿ�����
     * @access public
     * @param  string $name ģ�������
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->data[$name]);
    }
}
