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
 * Redis�����������ʺϵ���������ǰ�˴���ʵ�ָ߿��õĳ������������
 * ����Ҫ��ҵ���ʵ�ֶ�д���롢����ʹ��RedisCluster��������ʹ��Redisd����
 *
 * Ҫ��װphpredis��չ��https://github.com/nicolasff/phpredis
 * @author    ��Ե <130775@qq.com>
 */
class Redis extends Driver implements CacheHandlerInterface
{
    /**
     * ���ò���
     * @var array
     */
    protected $options = [
        'host'       => '127.0.0.1',
        'port'       => 6379,
        'password'   => '',
        'select'     => 0,
        'timeout'    => 0,
        'expire'     => 0,
        'persistent' => false,
        'prefix'     => '',
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
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }

        if (extension_loaded('redis')) {
            $this->handler = new \Redis;

            if ($this->options['persistent']) {
                $this->handler->pconnect($this->options['host'], (int) $this->options['port'], $this->options['timeout'], 'persistent_id_' . $this->options['select']);
            } else {
                $this->handler->connect($this->options['host'], (int) $this->options['port'], $this->options['timeout']);
            }

            if ('' != $this->options['password']) {
                $this->handler->auth($this->options['password']);
            }
        } elseif (class_exists('\Predis\Client')) {
            $params = [];
            foreach ($this->options as $key => $val) {
                if (in_array($key, ['aggregate', 'cluster', 'connections', 'exceptions', 'prefix', 'profile', 'replication', 'parameters'])) {
                    $params[$key] = $val;
                    unset($this->options[$key]);
                }
            }

            if ('' == $this->options['password']) {
                unset($this->options['password']);
            }

            $this->handler = new \Predis\Client($this->options, $params);

            $this->options['prefix'] = '';
        } else {
            throw new \BadFunctionCallException('not support: redis');
        }

        if (0 != $this->options['select']) {
            $this->handler->select($this->options['select']);
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
        return $this->handler->exists($this->getCacheKey($name));
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

        $value = $this->handler->get($this->getCacheKey($name));

        if (is_null($value) || false === $value) {
            return $default;
        }

        return $this->unserialize($value);
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

        if ($expire) {
            $this->handler->setex($key, $expire, $value);
        } else {
            $this->handler->set($key, $value);
        }

        return true;
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

        return $this->handler->incrby($key, $step);
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

        $key = $this->getCacheKey($name);

        return $this->handler->decrby($key, $step);
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

        $this->handler->del($this->getCacheKey($name));
        return true;
    }

    /**
     * �������
     * @access public
     * @return bool
     */
    public function clear(): bool
    {
        $this->writeTimes++;

        $this->handler->flushDB();
        return true;
    }

    /**
     * ɾ�������ǩ
     * @access public
     * @param  array  $keys �����ʶ�б�
     * @return void
     */
    public function clearTag(array $keys): void
    {
        // ָ����ǩ���
        $this->handler->del($keys);
    }

    /**
     * ׷�ӣ����飩��������
     * @access public
     * @param  string $name �����ʶ
     * @param  mixed  $value ����
     * @return void
     */
    public function push(string $name, $value): void
    {
        $this->handler->sAdd($name, $value);
    }

    /**
     * ��ȡ��ǩ�����Ļ����ʶ
     * @access public
     * @param  string $tag �����ǩ
     * @return array
     */
    public function getTagItems(string $tag): array
    {
        return $this->handler->sMembers($tag);
    }

}
