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

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use think\cache\CacheItem;
use think\cache\Driver;
use think\cache\TagSet;
use think\exception\InvalidArgumentException;

/**
 * ���������
 */
class Cache implements CacheItemPoolInterface
{
    /**
     * �������
     * @var array
     */
    protected $data = [];

    /**
     * ���ڱ���Ļ������
     * @var array
     */
    protected $deferred = [];

    /**
     * ����ʵ��
     * @var array
     */
    protected $instance = [];

    /**
     * ���ò���
     * @var array
     */
    protected $config = [];

    /**
     * �������
     * @var object
     */
    protected $handler;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public static function __make(Config $config)
    {
        return new static($config->get('cache'));
    }

    /**
     * ���ӻ���
     * @access public
     * @param  array $options  ��������
     * @param  bool  $force ǿ����������
     * @return Driver
     */
    public function connect(array $options = [], bool $force = false): Driver
    {
        $name = md5(serialize($options));

        if ($force || !isset($this->instance[$name])) {
            $type = !empty($options['type']) ? $options['type'] : 'File';

            $this->instance[$name] = App::factory($type, '\\think\\cache\\driver\\', $options);
        }

        return $this->instance[$name];
    }

    /**
     * �Զ���ʼ������
     * @access public
     * @param  array $options ��������
     * @param  bool  $force   ǿ�Ƹ���
     * @return Driver
     */
    public function init(array $options = [], bool $force = false): Driver
    {
        if (is_null($this->handler) || $force) {
            $options = !empty($options) ? $options : $this->config;

            if (isset($options['type']) && 'complex' == $options['type']) {
                $default = $options['default'];
                $options = $options[$default['type']] ?? $default;
            }

            $this->handler = $this->connect($options);
        }

        return $this->handler;
    }

    /**
     * ��������
     * @access public
     * @param  array $config ���ò���
     * @return void
     */
    public function config(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * �л��������� ��Ҫ���� cache.type Ϊ complex
     * @access public
     * @param  string $name  �����ʶ
     * @param  bool   $force ǿ�Ƹ���
     * @return Driver
     */
    public function store(string $name = '', bool $force = false): Driver
    {
        if ('' !== $name && 'complex' == $this->config['type']) {
            return $this->connect($this->config[$name], $force);
        }

        return $this->init([], $force);
    }

    /**
     * ��ȡ����
     * @access public
     * @param  string $key ���������
     * @param  mixed  $default Ĭ��ֵ
     * @return mixed
     */
    public function get(string $key, $default = false)
    {
        return $this->init()->get($key, $default);
    }

    /**
     * д�뻺��
     * @access public
     * @param  string        $name ���������
     * @param  mixed         $value  �洢����
     * @param  int|\DateTime $expire  ��Чʱ�� 0Ϊ����
     * @return bool
     */
    public function set(string $name, $value, $expire = null): bool
    {
        return $this->init()->set($name, $value, $expire);
    }

    /**
     * ׷�ӻ���
     * @access public
     * @param  string $name ���������
     * @param  mixed  $value  �洢����
     * @return void
     */
    public function push(string $name, $value): void
    {
        $this->init()->push($name, $value);
    }

    /**
     * ��ȡ��ɾ������
     * @access public
     * @param  string $name ���������
     * @return mixed
     */
    public function pull(string $name)
    {
        return $this->init()->pull($name);
    }

    /**
     * �����������д�뻺��
     * @access public
     * @param  string $name ���������
     * @param  mixed  $value  �洢����
     * @param  int    $expire  ��Чʱ�� 0Ϊ����
     * @return mixed
     */
    public function remember(string $name, $value, $expire = null)
    {
        return $this->init()->remember($name, $value, $expire);
    }

    /**
     * ɾ������
     * @access public
     * @param  string $name ���������
     * @return bool
     */
    public function delete(string $key): bool
    {
        return $this->init()->delete($key);
    }

    /**
     * �жϻ����Ƿ����
     * @access public
     * @param  string $name ���������
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->init()->has($key);
    }

    /**
     * �����ǩ
     * @access public
     * @param  string|array $name ��ǩ��
     * @return TagSet
     */
    public function tag($name): TagSet
    {
        return $this->init()->tag($name);
    }

    /**
     * ���ؾ�����󣬿�ִ�������߼�����
     *
     * @access public
     * @return object
     */
    public function handler()
    {
        return $this->init()->handler();
    }

    /**
     * ���ء�������Ӧ��һ�������
     * @access public
     * @param  string $key �����ʶ
     * @return CacheItemInterface
     * @throws InvalidArgumentException
     */
    public function getItem($key): CacheItem
    {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }

        $cacheItem = new CacheItem($key);

        if ($this->has($key)) {
            $cacheItem->set($this->get($key));
        }

        $this->data[$key] = $cacheItem;

        return $cacheItem;
    }

    /**
     * ����һ���ɹ������Ļ�����ϡ�
     * @access public
     * @param  array $keys
     * @return array|\Traversable
     * @throws InvalidArgumentException
     */
    public function getItems(array $keys = []): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[] = $this->getItem($key);
        }

        return $result;
    }

    /**
     * ��黺��ϵͳ���Ƿ��С�������Ӧ�Ļ����
     * @access public
     * @param  string $key
     * @return bool
     * @throws InvalidArgumentException
     */
    public function hasItem($key): bool
    {
        return $this->has($key);
    }

    /**
     * ��ջ����
     * @access public
     * @return bool
     */
    public function clear(): bool
    {
        return $this->init()->clear();
    }

    /**
     * �ӻ�������Ƴ�ĳ��������
     * @access public
     * @param  string $key
     * @return bool
     * @throws InvalidArgumentException
     */
    public function deleteItem($key): bool
    {
        return $this->delete($key);
    }

    /**
     * �ӻ�������Ƴ����������
     * @access public
     * @param  array $keys
     * @return bool
     * @throws InvalidArgumentException
     */
    public function deleteItems(array $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    /**
     * ����Ϊ��CacheItemInterface�����������ݳ־û���
     * @access public
     * @param  CacheItemInterface $item
     * @return bool
     */
    public function save(CacheItemInterface $item): bool
    {
        if ($item->getKey()) {
            return $this->set($item->getKey(), $item->get(), $item->getExpire());
        }

        return false;
    }

    /**
     * �Ժ�Ϊ��CacheItemInterface�����������ݳ־û���
     * @access public
     * @param  CacheItemInterface $item
     * @return bool
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        $this->deferred[$item->getKey()] = $item;
        return true;
    }

    /**
     * �ύ���е����ڶ�����ȴ����������ݳ־ò㣬��� `saveDeferred()` ʹ��
     * @access public
     * @return bool
     */
    public function commit(): bool
    {
        foreach ($this->deferred as $key => $item) {
            $result = $this->save($item);
            unset($this->deferred[$key]);

            if (false === $result) {
                return false;
            }
        }
        return true;
    }

    public function __call($method, $args)
    {
        return call_user_func_array([$this->init(), $method], $args);
    }

    public function __destruct()
    {
        if (!empty($this->deferred)) {
            $this->commit();
        }
    }

}
