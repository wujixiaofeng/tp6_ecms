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
 * ���ù�����
 */
class Config
{
    /**
     * ���ò���
     * @var array
     */
    protected $config = [];

    /**
     * �����ļ�Ŀ¼
     * @var string
     */
    protected $path;

    /**
     * �����ļ���׺
     * @var string
     */
    protected $ext;

    /**
     * ���췽��
     * @access public
     */
    public function __construct(string $path = null, string $ext = '.php')
    {
        $this->path = $path ?: '';
        $this->ext  = $ext;
    }

    public static function __make(App $app)
    {
        $path = $app->getConfigPath();
        $ext  = $app->getConfigExt();

        return new static($path, $ext);
    }

    /**
     * ���������ļ������ָ�ʽ��
     * @access public
     * @param  string $file �����ļ���
     * @param  string $name һ��������
     * @return array
     */
    public function load(string $file, string $name = ''): array
    {
        if (is_file($file)) {
            $filename = $file;
        } elseif (is_file($this->path . $file . $this->ext)) {
            $filename = $this->path . $file . $this->ext;
        }

        if (isset($filename)) {
            return $this->parse($filename, $name);
        }

        return $this->config;
    }

    /**
     * ���������ļ�
     * @access public
     * @param  string $file �����ļ���
     * @param  string $name һ��������
     * @return array
     */
    protected function parse(string $file, string $name): array
    {
        $type = pathinfo($file, PATHINFO_EXTENSION);

        switch ($type) {
            case 'php':
                $config = include $file;
                break;
            case 'yml':
            case 'yaml':
                if (function_exists('yaml_parse_file')) {
                    $config = yaml_parse_file($file);
                }
                break;
            case 'ini':
                $config = parse_ini_file($file, true, INI_SCANNER_TYPED) ?: [];
                break;
            case 'json':
                $config = json_decode(file_get_contents($file), true);
                break;
        }

        return isset($config) && is_array($config) ? $this->set($config, strtolower($name)) : [];
    }

    /**
     * ��������Ƿ����
     * @access public
     * @param  string $name ���ò�������֧�ֶ༶���� .�ŷָ
     * @return bool
     */
    public function has(string $name): bool
    {
        return !is_null($this->get($name));
    }

    /**
     * ��ȡһ������
     * @access protected
     * @param  string $name һ��������
     * @return array
     */
    protected function pull(string $name): array
    {
        $name = strtolower($name);

        return $this->config[$name] ?? [];
    }

    /**
     * ��ȡ���ò��� Ϊ�����ȡ��������
     * @access public
     * @param  string $name    ���ò�������֧�ֶ༶���� .�ŷָ
     * @param  mixed  $default Ĭ��ֵ
     * @return mixed
     */
    public function get(string $name = null, $default = null)
    {
        // �޲���ʱ��ȡ����
        if (empty($name)) {
            return $this->config;
        }

        if (false === strpos($name, '.')) {
            return $this->pull($name);
        }

        $name    = explode('.', $name);
        $name[0] = strtolower($name[0]);
        $config  = $this->config;

        // ��.��ֳɶ�ά��������ж�
        foreach ($name as $val) {
            if (isset($config[$val])) {
                $config = $config[$val];
            } else {
                return $default;
            }
        }

        return $config;
    }

    /**
     * �������ò��� nameΪ������Ϊ��������
     * @access public
     * @param  array  $config ���ò���
     * @param  string $name ������
     * @return array
     */
    public function set(array $config, string $name = null): array
    {
        if (!empty($name)) {
            if (isset($this->config[$name])) {
                $result = array_merge($this->config[$name], $config);
            } else {
                $result = $config;
            }

            $this->config[$name] = $result;
        } else {
            $result = $this->config = array_merge($this->config, array_change_key_case($config));
        }

        return $result;
    }

}
