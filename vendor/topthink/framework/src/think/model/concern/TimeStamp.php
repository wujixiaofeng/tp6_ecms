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

namespace think\model\concern;

use DateTime;

/**
 * �Զ�ʱ���
 */
trait TimeStamp
{
    /**
     * �Ƿ���Ҫ�Զ�д��ʱ��� �������Ϊ�ַ��� ���ʾʱ���ֶε�����
     * @var bool|string
     */
    protected $autoWriteTimestamp;

    /**
     * ����ʱ���ֶ� false��ʾ�ر�
     * @var false|string
     */
    protected $createTime = 'create_time';

    /**
     * ����ʱ���ֶ� false��ʾ�ر�
     * @var false|string
     */
    protected $updateTime = 'update_time';

    /**
     * ʱ���ֶ���ʾ��ʽ
     * @var string
     */
    protected $dateFormat;

    /**
     * �Ƿ���Ҫ�Զ�д��ʱ���ֶ�
     * @access public
     * @param  bool|string $auto
     * @return $this
     */
    public function isAutoWriteTimestamp($auto)
    {
        $this->autoWriteTimestamp = $auto;

        return $this;
    }

    /**
     * ��ȡ�Զ�д��ʱ���ֶ�
     * @access public
     * @return bool|string
     */
    public function getAutoWriteTimestamp()
    {
        return $this->autoWriteTimestamp;
    }

    /**
     * ����ʱ���ֶθ�ʽ��
     * @access public
     * @param  string|false $format
     * @return $this
     */
    public function setDateFormat($format)
    {
        $this->dateFormat = $format;

        return $this;
    }

    /**
     * ��ȡ�Զ�д��ʱ���ֶ�
     * @access public
     * @return string|false
     */
    public function getDateFormat()
    {
        return $this->dateFormat;
    }

    /**
     * �Զ�д��ʱ���
     * @access protected
     * @param  string $name ʱ����ֶ�
     * @return mixed
     */
    protected function autoWriteTimestamp(string $name)
    {
        $value = time();

        if (isset($this->type[$name])) {
            $type = $this->type[$name];

            if (strpos($type, ':')) {
                list($type, $param) = explode(':', $type, 2);
            }

            switch ($type) {
                case 'datetime':
                case 'date':
                case 'timestamp':
                    $value = $this->formatDateTime('Y-m-d H:i:s.u');
                    break;
                default:
                    if (false !== strpos($type, '\\')) {
                        // ��������д��
                        $value = new $type();
                        if (method_exists($value, '__toString')) {
                            // ��������д��
                            $value = $value->__toString();
                        }
                    }
            }
        } elseif (is_string($this->autoWriteTimestamp) && in_array(strtolower($this->autoWriteTimestamp),
            ['datetime', 'date', 'timestamp'])) {
            $value = $this->formatDateTime('Y-m-d H:i:s.u');
        }

        return $value;
    }

    /**
     * ʱ�������ֶθ�ʽ������
     * @access protected
     * @param  mixed $format    ���ڸ�ʽ
     * @param  mixed $time      ʱ�����ڱ��ʽ
     * @param  bool  $timestamp ʱ����ʽ�Ƿ�Ϊʱ���
     * @return mixed
     */
    protected function formatDateTime($format, $time = 'now', bool $timestamp = false)
    {
        if (empty($time)) {
            return;
        }

        if (false === $format) {
            return $time;
        } elseif (false !== strpos($format, '\\')) {
            return new $format($time);
        }

        if ($time instanceof DateTime) {
            $dateTime = $time;
        } elseif ($timestamp) {
            $dateTime = new DateTime();
            $dateTime->setTimestamp((int) $time);
        } else {
            $dateTime = new DateTime($time);
        }

        return $dateTime->format($format);
    }

    /**
     * ��ȡʱ���ֶ�ֵ
     * @access protected
     * @param  mixed   $value
     * @return mixed
     */
    protected function getTimestampValue($value)
    {
        if (is_string($this->autoWriteTimestamp) && in_array(strtolower($this->autoWriteTimestamp), [
            'datetime', 'date', 'timestamp',
        ])) {
            $value = $this->formatDateTime($this->dateFormat, $value);
        } else {
            $value = $this->formatDateTime($this->dateFormat, $value, true);
        }

        return $value;
    }
}
