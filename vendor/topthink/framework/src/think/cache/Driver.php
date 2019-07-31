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

namespace think\cache;

use DateInterval;
use DateTime;
use DateTimeInterface;
use Psr\SimpleCache\CacheInterface;
use think\Container;
use think\exception\InvalidArgumentException;

/**
 * ���������
 */
abstract class Driver implements CacheInterface
{
    /**
     * �������
     * @var object
     */
    protected $handler = null;

    /**
     * �����ȡ����
     * @var integer
     */
    protected $readTimes = 0;

    /**
     * ����д�����
     * @var integer
     */
    protected $writeTimes = 0;

    /**
     * �������
     * @var array
     */
    protected $options = [];

    /**
     * �����ǩ
     * @var array
     */
    protected $tag = [];

    /**
     * ��ȡ��Ч��
     * @access protected
     * @param  integer|DateTimeInterface|DateInterval $expire ��Ч��
     * @return int
     */
    protected function getExpireTime($expire): int
    {
        if ($expire instanceof DateTimeInterface) {
            $expire = $expire->getTimestamp() - time();
        } elseif ($expire instanceof DateInterval) {
            $expire = DateTime::createFromFormat('U', time())
                ->add($expire)
                ->format('U');
        }

        return (int) $expire;
    }

    /**
     * ��ȡʵ�ʵĻ����ʶ
     * @access public
     * @param  string $name ������
     * @return string
     */
    public function getCacheKey(string $name): string
    {
        return $this->options['prefix'] . $name;
    }

    /**
     * ��ȡ���沢ɾ��
     * @access public
     * @param  string $name ���������
     * @return mixed
     */
    public function pull(string $name)
    {
        $result = $this->get($name, false);

        if ($result) {
            $this->delete($name);
            return $result;
        }
    }

    /**
     * ׷�ӣ����飩����
     * @access public
     * @param  string $name ���������
     * @param  mixed  $value  �洢����
     * @return void
     */
    public function push(string $name, $value): void
    {
        $item = $this->get($name, []);

        if (!is_array($item)) {
            throw new InvalidArgumentException('only array cache can be push');
        }

        $item[] = $value;

        if (count($item) > 1000) {
            array_shift($item);
        }

        $item = array_unique($item);

        $this->set($name, $item);
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
        if ($this->has($name)) {
            return $this->get($name);
        }

        $time = time();

        while ($time + 5 > time() && $this->has($name . '_lock')) {
            // ����������ȴ�
            usleep(200000);
        }

        try {
            // ����
            $this->set($name . '_lock', true);

            if ($value instanceof \Closure) {
                // ��ȡ��������
                $value = Container::getInstance()->invokeFunction($value);
            }

            // ��������
            $this->set($name, $value, $expire);

            // ����
            $this->delete($name . '_lock');
        } catch (\Exception | \throwable $e) {
            $this->delete($name . '_lock');
            throw $e;
        }

        return $value;
    }

    /**
     * �����ǩ
     * @access public
     * @param  string|array $name ��ǩ��
     * @return $this
     */
    public function tag($name)
    {
        $name = (array) $name;
        $key  = implode('-', $name);

        if (!isset($this->tag[$key])) {
            $name = array_map(function ($val) {
                return $this->getTagKey($val);
            }, $name);
            $this->tag[$key] = new TagSet($name, $this);
        }

        return $this->tag[$key];
    }

    /**
     * ��ȡ��ǩ�����Ļ����ʶ
     * @access public
     * @param  string $tag ��ǩ��ʶ
     * @return array
     */
    public function getTagItems(string $tag): array
    {
        $name = $this->getTagKey($tag);
        return $this->get($name, []);
    }

    /**
     * ��ȡʵ�ʱ�ǩ��
     * @access public
     * @param  string $tag ��ǩ��
     * @return string
     */
    public function getTagKey(string $tag): string
    {
        return $this->options['tag_prefix'] . md5($tag);
    }

    /**
     * ���л�����
     * @access protected
     * @param  mixed $data ��������
     * @return string
     */
    protected function serialize($data): string
    {
        $serialize = $this->options['serialize'][0] ?? '\think\App::serialize';

        return $serialize($data);
    }

    /**
     * �����л�����
     * @access protected
     * @param  string $data ��������
     * @return mixed
     */
    protected function unserialize(string $data)
    {
        $unserialize = $this->options['serialize'][1] ?? '\think\App::unserialize';

        return $unserialize($data);
    }

    /**
     * ���ؾ�����󣬿�ִ�������߼�����
     *
     * @access public
     * @return object
     */
    public function handler()
    {
        return $this->handler;
    }

    /**
     * ���ػ����ȡ����
     * @access public
     * @return int
     */
    public function getReadTimes(): int
    {
        return $this->readTimes;
    }

    /**
     * ���ػ���д�����
     * @access public
     * @return int
     */
    public function getWriteTimes(): int
    {
        return $this->writeTimes;
    }

    /**
     * ��ȡ����
     * @access public
     * @param  iterable $keys ���������
     * @param  mixed    $default Ĭ��ֵ
     * @return iterable
     * @throws InvalidArgumentException
     */
    public function getMultiple($keys, $default = null): iterable
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    /**
     * д�뻺��
     * @access public
     * @param  iterable               $values ��������
     * @param  null|int|\DateInterval $ttl    ��Чʱ�� 0Ϊ����
     * @return bool
     */
    public function setMultiple($values, $ttl = null): bool
    {
        foreach ($values as $key => $val) {
            $result = $this->set($key, $val, $ttl);

            if (false === $result) {
                return false;
            }
        }

        return true;
    }

    /**
     * ɾ������
     * @access public
     * @param iterable $keys ���������
     * @return bool
     * @throws InvalidArgumentException
     */
    public function deleteMultiple($keys): bool
    {
        foreach ($keys as $key) {
            $result = $this->delete($key);

            if (false === $result) {
                return false;
            }
        }

        return true;
    }

    public function __call($method, $args)
    {
        return call_user_func_array([$this->handler, $method], $args);
    }
}
