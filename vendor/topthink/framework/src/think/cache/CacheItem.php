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
use Psr\Cache\CacheItemInterface;
use think\exception\InvalidArgumentException;

/**
 * CacheItemʵ����
 */
class CacheItem implements CacheItemInterface
{
    /**
     * ����Key
     * @var string
     */
    protected $key;

    /**
     * ��������
     * @var mixed
     */
    protected $value;

    /**
     * ����ʱ��
     * @var int|DateTimeInterface
     */
    protected $expire;

    /**
     * ����tag
     * @var string
     */
    protected $tag;

    /**
     * �����Ƿ�����
     * @var bool
     */
    protected $isHit = false;

    public function __construct(string $key = null)
    {
        $this->key = $key;
    }

    /**
     * Ϊ�˻��������á�����
     * @access public
     * @param  string $key
     * @return $this
     */
    public function setKey(string $key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * ���ص�ǰ������ġ�����
     * @access public
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * ���ص�ǰ���������Ч��
     * @access public
     * @return DateTimeInterface|int|null
     */
    public function getExpire()
    {
        if ($this->expire instanceof DateTimeInterface) {
            return $this->expire;
        }

        return $this->expire ? $this->expire - time() : null;
    }

    /**
     * ��ȡ����Tag
     * @access public
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * ƾ��˻�����ġ������ӻ���ϵͳ����ȡ��������
     * @access public
     * @return mixed
     */
    public function get()
    {
        return $this->value;
    }

    /**
     * ȷ�ϻ�����ļ���Ƿ�����
     * @access public
     * @return bool
     */
    public function isHit(): bool
    {
        return $this->isHit;
    }

    /**
     * Ϊ�˻��������á�ֵ��
     * @access public
     * @param  mixed $value
     * @return $this
     */
    public function set($value)
    {
        $this->value = $value;
        $this->isHit = true;
        return $this;
    }

    /**
     * Ϊ�˻���������������ǩ
     * @access public
     * @param  string $tag
     * @return $this
     */
    public function tag(string $tag = null)
    {
        $this->tag = $tag;
        return $this;
    }

    /**
     * ���û��������Ч��
     * @access public
     * @param  mixed $expire
     * @return $this
     */
    public function expire($expire)
    {
        if (is_null($expire)) {
            $this->expire = null;
        } elseif (is_numeric($expire) || $expire instanceof DateInterval) {
            $this->expiresAfter($expire);
        } elseif ($expire instanceof DateTimeInterface) {
            $this->expire = $expire;
        } else {
            throw new InvalidArgumentException('not support datetime');
        }

        return $this;
    }

    /**
     * ���û������׼ȷ����ʱ���
     * @access public
     * @param  DateTimeInterface $expiration
     * @return $this
     */
    public function expiresAt($expiration)
    {
        if ($expiration instanceof DateTimeInterface) {
            $this->expire = $expiration;
        } else {
            throw new InvalidArgumentException('not support datetime');
        }

        return $this;
    }

    /**
     * ���û�����Ĺ���ʱ��
     * @access public
     * @param int|DateInterval $timeInterval
     * @return $this
     * @throws InvalidArgumentException
     */
    public function expiresAfter($timeInterval)
    {
        if ($timeInterval instanceof DateInterval) {
            $this->expire = (int) DateTime::createFromFormat('U', time())->add($timeInterval)->format('U');
        } elseif (is_numeric($timeInterval)) {
            $this->expire = $timeInterval + time();
        } else {
            throw new InvalidArgumentException('not support datetime');
        }

        return $this;
    }

}
