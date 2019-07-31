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
 * Memcached������
 */
class Memcached extends Driver implements CacheHandlerInterface
{
    /**
     * ���ò���
     * @var array
     */
    protected $options = [
        'host'       => '127.0.0.1',
        'port'       => 11211,
        'expire'     => 0,
        'timeout'    => 0, // ��ʱʱ�䣨��λ�����룩
        'prefix'     => '',
        'username'   => '', //�˺�
        'password'   => '', //����
        'option'     => [],
        'tag_prefix' => 'tag:',
        'serialize'  => [],
    ];

    /**
     * �ܹ�����
     * @access public
     * @param  array $options �������
     */
    public function __construct(array $options = [])
    {
        if (!extension_loaded('memcached')) {
            throw new \BadFunctionCallException('not support: memcached');
        }

        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }

        $this->handler = new \Memcached;

        if (!empty($this->options['option'])) {
            $this->handler->setOptions($this->options['option']);
        }

        // �������ӳ�ʱʱ�䣨��λ�����룩
        if ($this->options['timeout'] > 0) {
            $this->handler->setOption(\Memcached::OPT_CONNECT_TIMEOUT, $this->options['timeout']);
        }

        // ֧�ּ�Ⱥ
        $hosts = (array) $this->options['host'];
        $ports = (array) $this->options['port'];
        if (empty($ports[0])) {
            $ports[0] = 11211;
        }

        // ��������
        $servers = [];
        foreach ($hosts as $i => $host) {
            $servers[] = [$host, $ports[$i] ?? $ports[0], 1];
        }

        $this->handler->addServers($servers);

        if ('' != $this->options['username']) {
            $this->handler->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
            $this->handler->setSaslAuthData($this->options['username'], $this->options['password']);
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
        $key = $this->getCacheKey($name);

        return $this->handler->get($key) ? true : false;
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

        $result = $this->handler->get($this->getCacheKey($name));

        return false !== $result ? $this->unserialize($result) : $default;
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

        if ($this->handler->set($key, $value, $expire)) {
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
        $this->writeTimes++;

        $key = $this->getCacheKey($name);

        if ($this->handler->get($key)) {
            return $this->handler->increment($key, $step);
        }

        return $this->handler->set($key, $step);
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
        $this->writeTimes++;

        $key   = $this->getCacheKey($name);
        $value = $this->handler->get($key) - $step;
        $res   = $this->handler->set($key, $value);

        return !$res ? false : $value;
    }

    /**
     * ɾ������
     * @access public
     * @param  string       $name ���������
     * @param  bool|false   $ttl
     * @return bool
     */
    public function delete($name, $ttl = false): bool
    {
        $this->writeTimes++;

        $key = $this->getCacheKey($name);

        return false === $ttl ?
        $this->handler->delete($key) :
        $this->handler->delete($key, $ttl);
    }

    /**
     * �������
     * @access public
     * @return bool
     */
    public function clear(): bool
    {
        $this->writeTimes++;

        return $this->handler->flush();
    }

    /**
     * ɾ�������ǩ
     * @access public
     * @param  array $keys �����ʶ�б�
     * @return void
     */
    public function clearTag(array $keys): void
    {
        $this->handler->deleteMulti($keys);
    }

}
