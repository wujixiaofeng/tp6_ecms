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

/**
 * ��ǩ����
 */
class TagSet
{
    /**
     * ��ǩ�Ļ���Key
     * @var array
     */
    protected $tag;

    /**
     * ������
     * @var Driver
     */
    protected $handler;

    /**
     * �ܹ�����
     * @access public
     * @param  array  $tag �����ǩ
     * @param  Driver $cache �������
     */
    public function __construct(array $tag, Driver $cache)
    {
        $this->tag     = $tag;
        $this->handler = $cache;
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
        $this->handler->set($name, $value, $expire);

        $this->append($name);

        return true;
    }

    /**
     * ׷�ӻ����ʶ����ǩ
     * @access public
     * @param  string $name ���������
     * @return void
     */
    public function append(string $name): void
    {
        $name = $this->handler->getCacheKey($name);

        foreach ($this->tag as $tag) {
            $this->handler->push($tag, $name);
        }
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
     * �����������д�뻺��
     * @access public
     * @param  string $name ���������
     * @param  mixed  $value  �洢����
     * @param  int    $expire  ��Чʱ�� 0Ϊ����
     * @return mixed
     */
    public function remember(string $name, $value, $expire = null)
    {
        $result = $this->handler->remember($name, $value, $expire);

        $this->append($name);

        return $result;
    }

    /**
     * �������
     * @access public
     * @return bool
     */
    public function clear(): bool
    {
        // ָ����ǩ���
        foreach ($this->tag as $tag) {
            $names = $this->handler->getTagItems($tag);

            $this->handler->clearTag($names);
            $this->handler->delete($tag);
        }

        return true;
    }
}
