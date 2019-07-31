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

namespace think\cache\driver;

use think\cache\Driver;
use think\contract\CacheHandlerInterface;

/**
 * Wincache��������
 */
class Wincache extends Driver implements CacheHandlerInterface
{
    /**
     * ���ò���
     * @var array
     */
    protected $options = [
        'prefix'     => '',
        'expire'     => 0,
        'tag_prefix' => 'tag:',
        'serialize'  => [],
    ];

    /**
     * �ܹ�����
     * @access public
     * @param  array $options �������
     * @throws \BadFunctionCallException
     */
    public function __construct(array $options = [])
    {
        if (!function_exists('wincache_ucache_info')) {
            throw new \BadFunctionCallException('not support: WinCache');
        }

        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
    }

    /**
     * �жϻ���
     * @access public
     * @param  string $name ���������
     * @return bool
     */
    public function has($name): bool
    {
        $this->readTimes++;

        $key = $this->getCacheKey($name);

        return wincache_ucache_exists($key);
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

        $key = $this->getCacheKey($name);

        return wincache_ucache_exists($key) ? $this->unserialize(wincache_ucache_get($key)) : $default;
    }

    /**
     * д�뻺��
     * @access public
     * @param  string            $name ���������
     * @param  mixed             $value  �洢����
     * @param  integer|\DateTime $expire  ��Чʱ�䣨�룩
     * @return bool
     */
    public function set($name, $value, $expire = null): bool
    {
        $this->writeTimes++;

        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }

        $key    = $this->getCacheKey($name);
        $expire = $this->getExpireTime($expire);
        $value  = $this->serialize($value);

        if (wincache_ucache_set($key, $value, $expire)) {
            return true;
        }

        return false;
    }

    /**
     * �������棨�����ֵ���棩
     * @access public
     * @param  string    $name ���������
     * @param  int       $step ����
     * @return false|int
     */
    public function inc(string $name, int $step = 1)
    {
        $this->writeTimes++;

        $key = $this->getCacheKey($name);

        return wincache_ucache_inc($key, $step);
    }

    /**
     * �Լ����棨�����ֵ���棩
     * @access public
     * @param  string    $name ���������
     * @param  int       $step ����
     * @return false|int
     */
    public function dec(string $name, int $step = 1)
    {
        $this->writeTimes++;

        $key = $this->getCacheKey($name);

        return wincache_ucache_dec($key, $step);
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

        return wincache_ucache_delete($this->getCacheKey($name));
    }

    /**
     * �������
     * @access public
     * @return bool
     */
    public function clear(): bool
    {
        $this->writeTimes++;
        return wincache_ucache_clear();
    }

    /**
     * ɾ�������ǩ
     * @access public
     * @param  array $keys �����ʶ�б�
     * @return void
     */
    public function clearTag(array $keys): void
    {
        wincache_ucache_delete($keys);
    }

}
