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

namespace think\response;

use think\Response;
use think\View as BaseView;

/**
 * View Response
 */
class View extends Response
{
    /**
     * �������
     * @var array
     */
    protected $options = [];

    /**
     * �������
     * @var array
     */
    protected $vars = [];

    /**
     * �������
     * @var mixed
     */
    protected $filter;

    /**
     * ���type
     * @var string
     */
    protected $contentType = 'text/html';

    /**
     * View����
     * @var BaseView
     */
    protected $view;

    /**
     * �Ƿ�������Ⱦ
     * @var bool
     */
    protected $isContent = false;

    public function __construct(BaseView $view, $data = '', int $code = 200)
    {
        parent::__construct($data, $code);
        $this->view = $view;
    }

    /**
     * �����Ƿ�Ϊ������Ⱦ
     * @access public
     * @param  bool $content
     * @return $this
     */
    public function isContent(bool $content = true)
    {
        $this->isContent = $content;
        return $this;
    }

    /**
     * ��������
     * @access protected
     * @param  mixed $data Ҫ���������
     * @return string
     */
    protected function output($data): string
    {
        // ��Ⱦģ�����
        return $this->view->filter($this->filter)
            ->assign($this->vars)
            ->fetch($data, $this->isContent);
    }

    /**
     * ��ȡ��ͼ����
     * @access public
     * @param  string $name ģ�����
     * @return mixed
     */
    public function getVars(string $name = null)
    {
        if (is_null($name)) {
            return $this->vars;
        } else {
            return $this->vars[$name] ?? null;
        }
    }

    /**
     * ģ�������ֵ
     * @access public
     * @param  array $vars  ����
     * @return $this
     */
    public function assign(array $vars)
    {
        $this->vars = array_merge($this->vars, $vars);

        return $this;
    }

    /**
     * ��ͼ���ݹ���
     * @access public
     * @param callable $filter
     * @return $this
     */
    public function filter(callable $filter = null)
    {
        $this->filter = $filter;
        return $this;
    }

    /**
     * ���ģ���Ƿ����
     * @access public
     * @param  string  $name ģ����
     * @return bool
     */
    public function exists(string $name): bool
    {
        return $this->view->exists($name);
    }

}
