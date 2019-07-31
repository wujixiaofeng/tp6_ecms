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
 * ��Ӧ���������
 */
class Response
{
    /**
     * ԭʼ����
     * @var mixed
     */
    protected $data;

    /**
     * ��ǰcontentType
     * @var string
     */
    protected $contentType = 'text/html';

    /**
     * �ַ���
     * @var string
     */
    protected $charset = 'gbk';

    /**
     * ״̬��
     * @var integer
     */
    protected $code = 200;

    /**
     * �Ƿ��������󻺴�
     * @var bool
     */
    protected $allowCache = true;

    /**
     * �������
     * @var array
     */
    protected $options = [];

    /**
     * header����
     * @var array
     */
    protected $header = [];

    /**
     * �������
     * @var string
     */
    protected $content = null;

    /**
     * Cookie����
     * @var Cookie
     */
    protected $cookie;

    /**
     * Session����
     * @var Session
     */
    protected $session;

    /**
     * �ܹ�����
     * @access public
     * @param  mixed $data    �������
     * @param  int   $code
     */
    public function __construct($data = '', int $code = 200)
    {
			if(defined('BIND_APP')&&BIND_APP=='mip')$this->charset='utf-8';
        $this->data($data);
        $this->code = $code;

        $this->contentType($this->contentType, $this->charset);
    }

    /**
     * ����Response����
     * @access public
     * @param  mixed  $data    �������
     * @param  string $type    �������
     * @param  int    $code
     * @return Response
     */
    public static function create($data = '', string $type = '', int $code = 200): Response
    {
        $class = false !== strpos($type, '\\') ? $type : '\\think\\response\\' . ucfirst(strtolower($type));

        if (class_exists($class)) {
            return Container::getInstance()->invokeClass($class, [$data, $code]);
        }

        return new static($data, $code);
    }

    /**
     * ����Cookie����
     * @access public
     * @param  Cookie $cookie Cookie����
     * @return $this
     */
    public function setCookie(Cookie $cookie)
    {
        $this->cookie = $cookie;
        return $this;
    }

    /**
     * ����Session����
     * @access public
     * @param  Session $session Session����
     * @return $this
     */
    public function setSession(Session $session)
    {
        $this->session = $session;
        return $this;
    }

    /**
     * �������ݵ��ͻ���
     * @access public
     * @return void
     * @throws \InvalidArgumentException
     */
    public function send(): void
    {
        // �����������
        $data = $this->getContent();

        if (!headers_sent() && !empty($this->header)) {
            // ����״̬��
            http_response_code($this->code);
            // ����ͷ����Ϣ
            foreach ($this->header as $name => $val) {
                header($name . (!is_null($val) ? ':' . $val : ''));
            }
        }

        $this->cookie->save();

        $this->sendData($data);

        if (function_exists('fastcgi_finish_request')) {
            // ���ҳ����Ӧ
            fastcgi_finish_request();
        }
    }

    /**
     * ��������
     * @access protected
     * @param  mixed $data Ҫ���������
     * @return mixed
     */
    protected function output($data)
    {
        return $data;
    }

    /**
     * �������
     * @access protected
     * @param string $data Ҫ���������
     * @return void
     */
    protected function sendData(string $data): void
    {
        echo $data;
    }

    /**
     * ����Ĳ���
     * @access public
     * @param  mixed $options �������
     * @return $this
     */
    public function options(array $options = [])
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * �����������
     * @access public
     * @param  mixed $data �������
     * @return $this
     */
    public function data($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * �Ƿ��������󻺴�
     * @access public
     * @param  bool $cache �������󻺴�
     * @return $this
     */
    public function allowCache(bool $cache)
    {
        $this->allowCache = $cache;

        return $this;
    }

    /**
     * �Ƿ��������󻺴�
     * @access public
     * @return $this
     */
    public function isAllowCache()
    {
        return $this->allowCache;
    }

    /**
     * ������Ӧͷ
     * @access public
     * @param  array $header  ����
     * @return $this
     */
    public function header(array $header = [])
    {
        $this->header = array_merge($this->header, $header);

        return $this;
    }

    /**
     * ����ҳ���������
     * @access public
     * @param  mixed $content
     * @return $this
     */
    public function content($content)
    {
        if (null !== $content && !is_string($content) && !is_numeric($content) && !is_callable([
            $content,
            '__toString',
        ])
        ) {
            throw new \InvalidArgumentException(sprintf('variable type error�� %s', gettype($content)));
        }

        $this->content = (string) $content;

        return $this;
    }

    /**
     * ����HTTP״̬
     * @access public
     * @param  integer $code ״̬��
     * @return $this
     */
    public function code(int $code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * LastModified
     * @access public
     * @param  string $time
     * @return $this
     */
    public function lastModified(string $time)
    {
        $this->header['Last-Modified'] = $time;

        return $this;
    }

    /**
     * Expires
     * @access public
     * @param  string $time
     * @return $this
     */
    public function expires(string $time)
    {
        $this->header['Expires'] = $time;

        return $this;
    }

    /**
     * ETag
     * @access public
     * @param  string $eTag
     * @return $this
     */
    public function eTag(string $eTag)
    {
        $this->header['ETag'] = $eTag;

        return $this;
    }

    /**
     * ҳ�滺�����
     * @access public
     * @param  string $cache ״̬��
     * @return $this
     */
    public function cacheControl(string $cache)
    {
        $this->header['Cache-control'] = $cache;

        return $this;
    }

    /**
     * ҳ���������
     * @access public
     * @param  string $contentType �������
     * @param  string $charset     �������
     * @return $this
     */
    public function contentType(string $contentType, string $charset = 'utf-8')
    {
        $this->header['Content-Type'] = $contentType . '; charset=' . $charset;

        return $this;
    }

    /**
     * ��ȡͷ����Ϣ
     * @access public
     * @param  string $name ͷ������
     * @return mixed
     */
    public function getHeader(string $name = '')
    {
        if (!empty($name)) {
            return $this->header[$name] ?? null;
        }

        return $this->header;
    }

    /**
     * ��ȡԭʼ����
     * @access public
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * ��ȡ�������
     * @access public
     * @return string
     */
    public function getContent(): string
    {
        if (null == $this->content) {
            $content = $this->output($this->data);

            if (null !== $content && !is_string($content) && !is_numeric($content) && !is_callable([
                $content,
                '__toString',
            ])
            ) {
                throw new \InvalidArgumentException(sprintf('variable type error�� %s', gettype($content)));
            }

            $this->content = (string) $content;
        }

        return $this->content;
    }

    /**
     * ��ȡ״̬��
     * @access public
     * @return integer
     */
    public function getCode(): int
    {
        return $this->code;
    }
}
