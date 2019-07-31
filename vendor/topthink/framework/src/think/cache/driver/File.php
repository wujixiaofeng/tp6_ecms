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

namespace think\cache\driver;

use think\App;
use think\cache\Driver;
use think\contract\CacheHandlerInterface;

/**
 * �ļ�������
 */
class File extends Driver implements CacheHandlerInterface
{
    /**
     * ���ò���
     * @var array
     */
    protected $options = [
        'expire'        => 0,
        'cache_subdir'  => true,
        'prefix'        => '',
        'path'          => '',
        'hash_type'     => 'md5',
        'data_compress' => false,
        'tag_prefix'    => 'tag:',
        'serialize'     => [],
    ];

    /**
     * ��Ч��
     * @var int|\DateTime
     */
    protected $expire;

    /**
     * �ܹ�����
     * @param App   $app Ӧ�ö���
     * @param array $options ����
     */
    public function __construct(App $app, array $options = [])
    {
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }

        if (empty($this->options['path'])) {
            $this->options['path'] = $app->getRuntimePath() . 'cache' . DIRECTORY_SEPARATOR;
        } elseif (substr($this->options['path'], -1) != DIRECTORY_SEPARATOR) {
            $this->options['path'] .= DIRECTORY_SEPARATOR;
        }
    }

    /**
     * ȡ�ñ����Ĵ洢�ļ���
     * @access public
     * @param  string $name ���������
     * @return string
     */
    public function getCacheKey(string $name): string
    {
        $name = hash($this->options['hash_type'], $name);

        if ($this->options['cache_subdir']) {
            // ʹ����Ŀ¼
            $name = substr($name, 0, 2) . DIRECTORY_SEPARATOR . substr($name, 2);
        }

        if ($this->options['prefix']) {
            $name = $this->options['prefix'] . DIRECTORY_SEPARATOR . $name;
        }

        return $this->options['path'] . $name . '.php';
    }

    /**
     * �жϻ����Ƿ����
     * @access public
     * @param  string $name ���������
     * @return bool
     */
    public function has($name): bool
    {
        return false !== $this->get($name) ? true : false;
    }

    /**
     * ��ȡ����
     * @access public
     * @param  string $name ���������
     * @param  mixed  $default Ĭ��ֵ
     * @return mixed
     */
    public function get($name, $default = false)
    {
        $this->readTimes++;

        $filename = $this->getCacheKey($name);

        if (!is_file($filename)) {
            return $default;
        }

        $content      = file_get_contents($filename);
        $this->expire = null;

        if (false !== $content) {
            $expire = (int) substr($content, 8, 12);
            if (0 != $expire && time() > filemtime($filename) + $expire) {
                //�������ɾ�������ļ�
                $this->unlink($filename);
                return $default;
            }

            $this->expire = $expire;
            $content      = substr($content, 32);

            if ($this->options['data_compress'] && function_exists('gzcompress')) {
                //��������ѹ��
                $content = gzuncompress($content);
            }
            return $this->unserialize($content);
        } else {
            return $default;
        }
    }

    /**
     * д�뻺��
     * @access public
     * @param  string        $name ���������
     * @param  mixed         $value  �洢����
     * @param  int|\DateTime $expire  ��Чʱ�� 0Ϊ����
     * @return bool
     */
    public function set($name, $value, $expire = null): bool
    {
        $this->writeTimes++;

        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }

        $expire   = $this->getExpireTime($expire);
        $filename = $this->getCacheKey($name, true);

        $dir = dirname($filename);

        if (!is_dir($dir)) {
            try {
                mkdir($dir, 0755, true);
            } catch (\Exception $e) {
                // ����ʧ��
            }
        }

        $data = $this->serialize($value);

        if ($this->options['data_compress'] && function_exists('gzcompress')) {
            //����ѹ��
            $data = gzcompress($data, 3);
        }

        $data   = "<?php\n//" . sprintf('%012d', $expire) . "\n exit();?>\n" . $data;
        $result = file_put_contents($filename, $data);

        if ($result) {
            clearstatcache();
            return true;
        }

        return false;
    }

    /**
     * �������棨�����ֵ���棩
     * @access public
     * @param  string $name ���������
     * @param  int    $step ����
     * @return false|int
     */
    public function inc(string $name, int $step = 1)
    {
        if ($this->has($name)) {
            $value  = $this->get($name) + $step;
            $expire = $this->expire;
        } else {
            $value  = $step;
            $expire = 0;
        }

        return $this->set($name, $value, $expire) ? $value : false;
    }

    /**
     * �Լ����棨�����ֵ���棩
     * @access public
     * @param  string $name ���������
     * @param  int    $step ����
     * @return false|int
     */
    public function dec(string $name, int $step = 1)
    {
        if ($this->has($name)) {
            $value  = $this->get($name) - $step;
            $expire = $this->expire;
        } else {
            $value  = -$step;
            $expire = 0;
        }

        return $this->set($name, $value, $expire) ? $value : false;
    }

    /**
     * ɾ������
     * @access public
     * @param  string $name ���������
     * @return bool
     */
    public function delete($name): bool
    {
        $this->writeTimes++;

        try {
            return $this->unlink($this->getCacheKey($name));
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * �������
     * @access public
     * @return bool
     */
    public function clear(): bool
    {
        $this->writeTimes++;

        $files = (array) glob($this->options['path'] . ($this->options['prefix'] ? $this->options['prefix'] . DIRECTORY_SEPARATOR : '') . '*');

        foreach ($files as $path) {
            if (is_dir($path)) {
                $matches = glob($path . DIRECTORY_SEPARATOR . '*.php');
                if (is_array($matches)) {
                    array_map('unlink', $matches);
                }
                rmdir($path);
            } else {
                unlink($path);
            }
        }

        return true;
    }

    /**
     * ɾ�������ǩ
     * @access public
     * @param  array $keys �����ʶ�б�
     * @return void
     */
    public function clearTag(array $keys): void
    {
        foreach ($keys as $key) {
            $this->unlink($key);
        }
    }

    /**
     * �ж��ļ��Ƿ���ں�ɾ��
     * @access private
     * @param  string $path
     * @return bool
     */
    private function unlink(string $path): bool
    {
        return is_file($path) && unlink($path);
    }

}
