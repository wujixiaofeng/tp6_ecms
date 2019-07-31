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

use think\Request;
use think\Response;
use think\Route;
use think\Session;

/**
 * Redirect Response
 */
class Redirect extends Response
{

    protected $options = [];

    // URL����
    protected $params = [];
    protected $route;
    protected $request;

    public function __construct(Route $route, Request $request, Session $session, $data = '', int $code = 302)
    {
        parent::__construct($data, $code);
        $this->route   = $route;
        $this->request = $request;
        $this->session = $session;

        $this->cacheControl('no-cache,must-revalidate');
    }

    /**
     * ��������
     * @access protected
     * @param  mixed $data Ҫ���������
     * @return string
     */
    protected function output($data): string
    {
        $this->header['Location'] = $this->getTargetUrl();

        return '';
    }

    /**
     * �ض���ֵ��ͨ��Session��
     * @access protected
     * @param  string|array  $name ��������������
     * @param  mixed         $value ֵ
     * @return $this
     */
    public function with($name, $value = null)
    {
        if (is_array($name)) {
            foreach ($name as $key => $val) {
                $this->session->flash($key, $val);
            }
        } else {
            $this->session->flash($name, $value);
        }

        return $this;
    }

    /**
     * ��ȡ��ת��ַ
     * @access public
     * @return string
     */
    public function getTargetUrl()
    {
        if (strpos($this->data, '://') || (0 === strpos($this->data, '/') && empty($this->params))) {
            return $this->data;
        } else {
            return $this->route->buildUrl($this->data, $this->params);
        }
    }

    public function params($params = [])
    {
        $this->params = $params;

        return $this;
    }

    /**
     * ��ס��ǰurl����ת
     * @access public
     * @return $this
     */
    public function remember()
    {
        $this->session->set('redirect_url', $this->request->url());

        return $this;
    }

    /**
     * ��ת���ϴμ�ס��url
     * @access public
     * @return $this
     */
    public function restore()
    {
        if ($this->session->has('redirect_url')) {
            $this->data = $this->session->get('redirect_url');
            $this->session->delete('redirect_url');
        }

        return $this;
    }
}
