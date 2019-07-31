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

use DateTimeInterface;

/**
 * Cookie������
 */
class Cookie
{
    /**
     * ���ò���
     * @var array
     */
    protected $config = [
        // cookie ����ʱ��
        'expire'   => 0,
        // cookie ����·��
        'path'     => '/',
        // cookie ��Ч����
        'domain'   => '',
        //  cookie ���ð�ȫ����
        'secure'   => false,
        // httponly����
        'httponly' => false,
    ];

    /**
     * Cookieд������
     * @var array
     */
    protected $cookie = [];

    /**
     * ��ǰRequest����
     * @var Request
     */
    protected $request;

    /**
     * ���췽��
     * @access public
     */
    public function __construct(Request $request, array $config = [])
    {
        $this->request = $request;
        $this->config  = array_merge($this->config, array_change_key_case($config));
    }

    public static function __make(Request $request, Config $config)
    {
        return new static($request, $config->get('cookie'));
    }

    /**
     * ��ȡcookie
     * @access public
     * @param  mixed  $name ��������
     * @param  string $default Ĭ��ֵ
     * @return mixed
     */
    public function get(string $name = '', $default = null)
    {
        return $this->request->cookie($name, $default);
    }

    /**
     * �Ƿ����Cookie����
     * @access public
     * @param  string $name ������
     * @return bool
     */
    public function has(string $name): bool
    {
        return $this->request->has($name, 'cookie');
    }

    /**
     * Cookie ����
     *
     * @access public
     * @param  string $name  cookie����
     * @param  string $value cookieֵ
     * @param  mixed  $option ��ѡ����
     * @return void
     */
    public function set(string $name, string $value, $option = null): void
    {
        // ��������(�Ḳ���a������)
        if (!is_null($option)) {
            if (is_numeric($option) || $option instanceof DateTimeInterface) {
                $option = ['expire' => $option];
            }

            $config = array_merge($this->config, array_change_key_case($option));
        } else {
            $config = $this->config;
        }

        if ($config['expire'] instanceof DateTimeInterface) {
            $expire = $config['expire']->getTimestamp();
        } else {
            $expire = !empty($config['expire']) ? time() + intval($config['expire']) : 0;
        }

        $this->setCookie($name, $value, $expire, $config);
    }

    /**
     * Cookie ����
     *
     * @access public
     * @param  string $name  cookie����
     * @param  string $value cookieֵ
     * @param  int    $expire ��Ч��
     * @param  array  $option ��ѡ����
     * @return void
     */
    protected function setCookie(string $name, string $value, int $expire, array $option = []): void
    {
        $this->cookie[$name] = [$value, $expire, $option];
    }

    /**
     * ���ñ���Cookie����
     * @access public
     * @param  string $name  cookie����
     * @param  string $value cookieֵ
     * @param  mixed  $option ��ѡ���� ���ܻ��� null|integer|string
     * @return void
     */
    public function forever(string $name, string $value = '', $option = null): void
    {
        if (is_null($option) || is_numeric($option)) {
            $option = [];
        }

        $option['expire'] = 315360000;

        $this->set($name, $value, $option);
    }

    /**
     * Cookieɾ��
     * @access public
     * @param  string $name cookie����
     * @return void
     */
    public function delete(string $name): void
    {
        $this->setCookie($name, '', time() - 3600, $this->config);
    }

    /**
     * ��ȡcookie��������
     * @access public
     * @return array
     */
    public function getCookie(): array
    {
        return $this->cookie;
    }

    /**
     * ����Cookie
     * @access public
     * @return void
     */
    public function save(): void
    {
        foreach ($this->cookie as $name => $val) {
            list($value, $expire, $option) = $val;

            $this->saveCookie($name, $value, $expire, $option['path'], $option['domain'], $option['secure'] ? true : false, $option['httponly'] ? true : false);
        }
    }

    /**
     * ����Cookie
     * @access public
     * @param  string $name cookie����
     * @param  string $value cookieֵ
     * @param  int    $expire cookie����ʱ��
     * @param  string $path ��Ч�ķ�����·��
     * @param  string $domain ��Ч����/������
     * @param  bool   $secure �Ƿ����ͨ��HTTPS
     * @param  bool   $httponly ����ͨ��HTTP����
     * @return void
     */
    protected function saveCookie(string $name, string $value, int $expire, string $path, string $domain, bool $secure, bool $httponly): void
    {
        setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }

}
