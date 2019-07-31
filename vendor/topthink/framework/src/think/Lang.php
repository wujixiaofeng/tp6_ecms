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
 * �����Թ�����
 */
class Lang
{
    /**
     * ���ò���
     * @var array
     */
    protected $config = [
        // Ĭ������
        'default_lang'    => 'zh-cn',
        // ����������б�
        'allow_lang_list' => [],
        // �Ƿ�ʹ��Cookie��¼
        'use_cookie'      => true,
        // ��չ���԰�
        'extend_list'     => [],
        // ������cookie����
        'cookie_var'      => 'think_lang',
        // �������Զ���������
        'detect_var'      => 'lang',
        // Accept-Languageת��Ϊ��Ӧ���԰�����
        'accept_language' => [
            'zh-hans-cn' => 'zh-cn',
        ],
        // �Ƿ�֧�����Է���
        'allow_group'     => false,
    ];

    /**
     * ��������Ϣ
     * @var array
     */
    private $lang = [];

    /**
     * ��ǰ����
     * @var string
     */
    private $range = 'zh-cn';

    /**
     * Request����
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
        $this->range   = $this->config['default_lang'];
    }

    public static function __make(Request $request, Config $config)
    {
        return new static($request, $config->get('lang'));
    }

    /**
     * ���õ�ǰ����
     * @access public
     * @param  string $name ����
     * @return void
     */
    public function setLangSet(string $lang): void
    {
        $this->range = $lang;
    }

    /**
     * ��ȡ��ǰ����
     * @access public
     * @return string
     */
    public function getLangSet(): string
    {
        return $this->range;
    }

    /**
     * ��ȡĬ������
     * @access public
     * @return string
     */
    public function defaultLangSet()
    {
        return $this->config['default_lang'];
    }

    /**
     * �������Զ���(�����ִ�Сд)
     * @access public
     * @param  string|array $file   �����ļ�
     * @param  string       $range  ����������
     * @return array
     */
    public function load($file, $range = ''): array
    {
        $range = $range ?: $this->range;
        if (!isset($this->lang[$range])) {
            $this->lang[$range] = [];
        }

        $lang = [];

        foreach ((array) $file as $_file) {
            if (is_file($_file)) {
                $result = $this->parse($_file);
                $lang   = array_change_key_case($result) + $lang;
            }
        }

        if (!empty($lang)) {
            $this->lang[$range] = $lang + $this->lang[$range];
        }

        return $this->lang[$range];
    }

    /**
     * ���������ļ�
     * @access protected
     * @param  string $file �����ļ���
     * @return array
     */
    protected function parse(string $file): array
    {
        $type = pathinfo($file, PATHINFO_EXTENSION);

        switch ($type) {
            case 'php':
                $result = include $file;
                break;
            case 'yml':
            case 'yaml':
                if (function_exists('yaml_parse_file')) {
                    $result = yaml_parse_file($file);
                }
                break;
        }

        return isset($result) && is_array($result) ? $result : [];
    }

    /**
     * �ж��Ƿ�������Զ���(�����ִ�Сд)
     * @access public
     * @param  string|null $name ���Ա���
     * @param  string      $range ����������
     * @return bool
     */
    public function has(string $name, string $range = ''): bool
    {
        $range = $range ?: $this->range;

        if ($this->config['allow_group'] && strpos($name, '.')) {
            list($name1, $name2) = explode('.', $name, 2);
            return isset($this->lang[$range][strtolower($name1)][$name2]);
        }

        return isset($this->lang[$range][strtolower($name)]);
    }

    /**
     * ��ȡ���Զ���(�����ִ�Сд)
     * @access public
     * @param  string|null $name ���Ա���
     * @param  array       $vars �����滻
     * @param  string      $range ����������
     * @return mixed
     */
    public function get(string $name = null, array $vars = [], string $range = '')
    {
        $range = $range ?: $this->range;

        // �ղ����������ж���
        if (is_null($name)) {
            return $this->lang[$range] ?? [];
        }

        if ($this->config['allow_group'] && strpos($name, '.')) {
            list($name1, $name2) = explode('.', $name, 2);

            $value = $this->lang[$range][strtolower($name1)][$name2] ?? $name;
        } else {
            $value = $this->lang[$range][strtolower($name)] ?? $name;
        }

        // ��������
        if (!empty($vars) && is_array($vars)) {
            /**
             * Notes:
             * Ϊ�˼��ķ��㣬�����������жϽ����ǲ�������ĵ�һ��Ԫ�ص�keyΪ����0
             * �����������õ���ϵͳ�� sprintf �����滻���÷���ο� sprintf ����
             */
            if (key($vars) === 0) {
                // ������������
                array_unshift($vars, $value);
                $value = call_user_func_array('sprintf', $vars);
            } else {
                // ������������
                $replace = array_keys($vars);
                foreach ($replace as &$v) {
                    $v = "{:{$v}}";
                }
                $value = str_replace($replace, $vars, $value);
            }
        }

        return $value;
    }

    /**
     * �Զ�������û�ȡ����ѡ��
     * @access public
     * @return string
     */
    public function detect(): string
    {
        // �Զ�������û�ȡ����ѡ��
        $langSet = '';

        if ($this->request->get($this->config['detect_var'])) {
            // url�����������Ա���
            $langSet = strtolower($this->request->get($this->config['detect_var']));
        } elseif ($this->request->cookie($this->config['cookie_var'])) {
            // Cookie�����������Ա���
            $langSet = strtolower($this->request->cookie($this->config['cookie_var']));
        } elseif ($this->request->server('HTTP_ACCEPT_LANGUAGE')) {
            // �Զ�������������
            preg_match('/^([a-z\d\-]+)/i', $this->request->server('HTTP_ACCEPT_LANGUAGE'), $matches);
            $langSet = strtolower($matches[1]);

            if (isset($this->config['accept_language'][$langSet])) {
                $langSet = $this->config['accept_language'][$langSet];
            }
        }

        if (empty($this->config['allow_lang_list']) || in_array($langSet, $this->config['allow_lang_list'])) {
            // �Ϸ�������
            $this->range = $langSet;
        }

        return $this->range;
    }

    /**
     * ���浱ǰ���Ե�Cookie
     * @access public
     * @param  Cookie $cookie Cookie����
     * @return void
     */
    public function saveToCookie(Cookie $cookie)
    {
        if ($this->config['use_cookie']) {
            $cookie->set($this->config['cookie_var'], $this->range);
        }
    }

}
