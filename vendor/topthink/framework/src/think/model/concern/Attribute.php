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

use InvalidArgumentException;
use think\App;
use think\db\Raw;
use think\model\Relation;

/**
 * ģ�����ݴ���
 */
trait Attribute
{
    /**
     * ���ݱ����� ��������ʹ�����鶨��
     * @var string|array
     */
    protected $pk = 'id';

    /**
     * ���ݱ��ֶ���Ϣ �������Զ���ȡ
     * @var array
     */
    protected $schema = [];

    /**
     * ��ǰ����д����ֶ�
     * @var array
     */
    protected $field = [];

    /**
     * �ֶ��Զ�����ת��
     * @var array
     */
    protected $type = [];

    /**
     * ���ݱ�����ֶ�
     * @var array
     */
    protected $disuse = [];

    /**
     * ���ݱ�ֻ���ֶ�
     * @var array
     */
    protected $readonly = [];

    /**
     * ��ǰģ������
     * @var array
     */
    private $data = [];

    /**
     * ԭʼ����
     * @var array
     */
    private $origin = [];

    /**
     * JSON���ݱ��ֶ�
     * @var array
     */
    protected $json = [];

    /**
     * JSON���ݱ��ֶ�����
     * @var array
     */
    protected $jsonType = [];

    /**
     * JSON����ȡ���Ƿ���Ҫת��Ϊ����
     * @var bool
     */
    protected $jsonAssoc = false;

    /**
     * �Ƿ��ϸ��ֶδ�Сд
     * @var bool
     */
    protected $strict = true;

    /**
     * �޸���ִ�м�¼
     * @var array
     */
    private $set = [];

    /**
     * ��̬��ȡ��
     * @var array
     */
    private $withAttr = [];

    /**
     * ��ȡģ�Ͷ��������
     * @access public
     * @return string|array
     */
    public function getPk()
    {
        return $this->pk;
    }

    /**
     * �ж�һ���ֶ����Ƿ�Ϊ�����ֶ�
     * @access public
     * @param  string $key ����
     * @return bool
     */
    protected function isPk(string $key): bool
    {
        $pk = $this->getPk();

        if (is_string($pk) && $pk == $key) {
            return true;
        } elseif (is_array($pk) && in_array($key, $pk)) {
            return true;
        }

        return false;
    }

    /**
     * ��ȡģ�Ͷ��������ֵ
     * @access public
     * @return mixed
     */
    public function getKey()
    {
        $pk = $this->getPk();

        if (is_string($pk) && array_key_exists($pk, $this->data)) {
            return $this->data[$pk];
        }

        return;
    }

    /**
     * ��������д����ֶ�
     * @access public
     * @param  array $field ����д����ֶ�
     * @return $this
     */
    public function allowField(array $field)
    {
        $this->field = $field;

        return $this;
    }

    /**
     * ����ֻ���ֶ�
     * @access public
     * @param  array $field ֻ���ֶ�
     * @return $this
     */
    public function readOnly(array $field)
    {
        $this->readonly = $field;

        return $this;
    }

    /**
     * ��ȡʵ�ʵ��ֶ���
     * @access public
     * @param  string $name �ֶ���
     * @return string
     */
    protected function getRealFieldName(string $name): string
    {
        return $this->strict ? $name : App::parseName($name);
    }

    /**
     * �������ݶ���ֵ
     * @access public
     * @param  array    $data  ����
     * @param  bool     $set   �Ƿ�����޸���
     * @param  array    $allow ������ֶ���
     * @return $this
     */
    public function data(array $data, bool $set = false, array $allow = [])
    {
        // �������
        $this->data = [];

        // �����ֶ�
        foreach ($this->disuse as $key) {
            if (array_key_exists($key, $data)) {
                unset($data[$key]);
            }
        }

        if (!empty($allow)) {
            $result = [];
            foreach ($allow as $name) {
                if (isset($data[$name])) {
                    $result[$name] = $data[$name];
                }
            }
            $data = $result;
        }

        if ($set) {
            // ���ݶ���ֵ
            $this->setAttrs($data);
        } else {
            $this->data = $data;
        }

        return $this;
    }

    /**
     * ����׷�����ݶ���ֵ
     * @access public
     * @param  array $data  ����
     * @param  bool  $set   �Ƿ���Ҫ�������ݴ���
     * @return $this
     */
    public function appendData(array $data, bool $set = false)
    {
        if ($set) {
            $this->setAttrs($data);
        } else {
            $this->data = array_merge($this->data, $data);
        }

        return $this;
    }

    /**
     * ��ȡ����ԭʼ���� ���������ָ���ֶη���null
     * @access public
     * @param  string $name �ֶ��� ���ջ�ȡȫ��
     * @return mixed
     */
    public function getOrigin(string $name = null)
    {
        if (is_null($name)) {
            return $this->origin;
        }

        return array_key_exists($name, $this->origin) ? $this->origin[$name] : null;
    }

    /**
     * ��ȡ����ԭʼ���� ���������ָ���ֶη���false
     * @access public
     * @param  string $name �ֶ��� ���ջ�ȡȫ��
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function getData(string $name = null)
    {
        if (is_null($name)) {
            return $this->data;
        }

        $fieldName = $this->getRealFieldName($name);

        if (array_key_exists($fieldName, $this->data)) {
            return $this->data[$fieldName];
        } elseif (array_key_exists($name, $this->relation)) {
            return $this->relation[$name];
        }

        throw new InvalidArgumentException('property not exists:' . static::class . '->' . $name);
    }

    /**
     * ��ȡ�仯������ ���ų�ֻ������
     * @access public
     * @return array
     */
    public function getChangedData(): array
    {
        $data = $this->force ? $this->data : array_udiff_assoc($this->data, $this->origin, function ($a, $b) {
            if ((empty($a) || empty($b)) && $a !== $b) {
                return 1;
            }

            return is_object($a) || $a != $b ? 1 : 0;
        });

        // ֻ���ֶβ��������
        foreach ($this->readonly as $key => $field) {
            if (isset($data[$field])) {
                unset($data[$field]);
            }
        }

        return $data;
    }

    /**
     * ֱ���������ݶ���ֵ
     * @access public
     * @param  string $name  ������
     * @param  mixed  $value ֵ
     * @return void
     */
    public function set(string $name, $value): void
    {
        $name = $this->getRealFieldName($name);

        $this->data[$name] = $value;
    }

    /**
     * ͨ���޸��� �����������ݶ���ֵ
     * @access public
     * @param  array $data  ����
     * @return void
     */
    public function setAttrs(array $data): void
    {
        // �������ݴ���
        foreach ($data as $key => $value) {
            $this->setAttr($key, $value, $data);
        }
    }

    /**
     * ͨ���޸��� �������ݶ���ֵ
     * @access public
     * @param  string $name  ������
     * @param  mixed  $value ����ֵ
     * @param  array  $data  ����
     * @return void
     */
    public function setAttr(string $name, $value, array $data = []): void
    {
        $name = $this->getRealFieldName($name);

        if (isset($this->set[$name])) {
            return;
        }

        if (is_null($value) && $this->autoWriteTimestamp && in_array($name, [$this->createTime, $this->updateTime])) {
            // �Զ�д���ʱ����ֶ�
            $value = $this->autoWriteTimestamp($name);
        } else {
            // ����޸���
            $method = 'set' . App::parseName($name, 1) . 'Attr';

            if (method_exists($this, $method)) {
                $array = $this->data;
                $value = $this->$method($value, array_merge($this->data, $data));

                $this->set[$name] = true;
                if (is_null($value) && $array !== $this->data) {
                    return;
                }
            } elseif (isset($this->type[$name])) {
                // ����ת��
                $value = $this->writeTransform($value, $this->type[$name]);
            }
        }

        // �������ݶ�������
        $this->data[$name] = $value;
    }

    /**
     * ����д�� ����ת��
     * @access protected
     * @param  mixed        $value ֵ
     * @param  string|array $type  Ҫת��������
     * @return mixed
     */
    protected function writeTransform($value, $type)
    {
        if (is_null($value)) {
            return;
        }

        if ($value instanceof Raw) {
            return $value;
        }

        if (is_array($type)) {
            list($type, $param) = $type;
        } elseif (strpos($type, ':')) {
            list($type, $param) = explode(':', $type, 2);
        }

        switch ($type) {
            case 'integer':
                $value = (int) $value;
                break;
            case 'float':
                if (empty($param)) {
                    $value = (float) $value;
                } else {
                    $value = (float) number_format($value, $param, '.', '');
                }
                break;
            case 'boolean':
                $value = (bool) $value;
                break;
            case 'timestamp':
                if (!is_numeric($value)) {
                    $value = strtotime($value);
                }
                break;
            case 'datetime':
                $value = is_numeric($value) ? $value : strtotime($value);
                $value = $this->formatDateTime('Y-m-d H:i:s.u', $value);
                break;
            case 'object':
                if (is_object($value)) {
                    $value = json_encode($value, JSON_FORCE_OBJECT);
                }
                break;
            case 'array':
                $value = (array) $value;
            case 'json':
                $option = !empty($param) ? (int) $param : JSON_UNESCAPED_UNICODE;
                $value  = json_encode($value, $option);
                break;
            case 'serialize':
                $value = serialize($value);
                break;
            default:
                if (is_object($value) && false !== strpos($type, '\\') && method_exists($value, '__toString')) {
                    // ��������
                    $value = $value->__toString();
                }
        }

        return $value;
    }

    /**
     * ��ȡ�� ��ȡ���ݶ����ֵ
     * @access public
     * @param  string $name ����
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function getAttr(string $name)
    {
        try {
            $relation = false;
            $value    = $this->getData($name);
        } catch (InvalidArgumentException $e) {
            $relation = $this->isRelationAttr($name);
            $value    = null;
        }

        return $this->getValue($name, $value, $relation);
    }

    /**
     * ��ȡ������ȡ�����������ݶ����ֵ
     * @access protected
     * @param  string      $name �ֶ�����
     * @param  mixed       $value �ֶ�ֵ
     * @param  bool|string $relation �Ƿ�Ϊ�������Ի��߹�����
     * @return mixed
     * @throws InvalidArgumentException
     */
    protected function getValue(string $name, $value, $relation = false)
    {
        // ������Ի�ȡ��
        $fieldName = $this->getRealFieldName($name);
        $method    = 'get' . App::parseName($name, 1) . 'Attr';

        if (isset($this->withAttr[$fieldName])) {
            if ($relation) {
                $value = $this->getRelationValue($relation);
            }

            if (in_array($fieldName, $this->json) && is_array($this->withAttr[$fieldName])) {
                $value = $this->getJsonValue($fieldName, $value);
            } else {
                $closure = $this->withAttr[$fieldName];
                $value   = $closure($value, $this->data);
            }
        } elseif (method_exists($this, $method)) {
            if ($relation) {
                $value = $this->getRelationValue($relation);
            }

            $value = $this->$method($value, $this->data);
        } elseif (isset($this->type[$fieldName])) {
            // ����ת��
            $value = $this->readTransform($value, $this->type[$fieldName]);
        } elseif ($this->autoWriteTimestamp && in_array($fieldName, [$this->createTime, $this->updateTime])) {
            $value = $this->getTimestampValue($value);
        } elseif ($relation) {
            $value = $this->getRelationValue($relation);
            // �����������ֵ
            $this->relation[$name] = $value;
        }

        return $value;
    }

    /**
     * ��ȡJSON�ֶ�����ֵ
     * @access protected
     * @param  string $name  ������
     * @param  mixed  $value JSON����
     * @return mixed
     */
    protected function getJsonValue($name, $value)
    {
        foreach ($this->withAttr[$name] as $key => $closure) {
            if ($this->jsonAssoc) {
                $value[$key] = $closure($value[$key], $value);
            } else {
                $value->$key = $closure($value->$key, $value);
            }
        }

        return $value;
    }

    /**
     * ��ȡ��������ֵ
     * @access protected
     * @param  string $relation ������
     * @return mixed
     */
    protected function getRelationValue(string $relation)
    {
        $modelRelation = $this->$relation();

        return $modelRelation instanceof Relation ? $this->getRelationData($modelRelation) : null;
    }

    /**
     * ���ݶ�ȡ ����ת��
     * @access protected
     * @param  mixed        $value ֵ
     * @param  string|array $type  Ҫת��������
     * @return mixed
     */
    protected function readTransform($value, $type)
    {
        if (is_null($value)) {
            return;
        }

        if (is_array($type)) {
            list($type, $param) = $type;
        } elseif (strpos($type, ':')) {
            list($type, $param) = explode(':', $type, 2);
        }

        switch ($type) {
            case 'integer':
                $value = (int) $value;
                break;
            case 'float':
                if (empty($param)) {
                    $value = (float) $value;
                } else {
                    $value = (float) number_format($value, $param, '.', '');
                }
                break;
            case 'boolean':
                $value = (bool) $value;
                break;
            case 'timestamp':
                if (!is_null($value)) {
                    $format = !empty($param) ? $param : $this->dateFormat;
                    $value  = $this->formatDateTime($format, $value, true);
                }
                break;
            case 'datetime':
                if (!is_null($value)) {
                    $format = !empty($param) ? $param : $this->dateFormat;
                    $value  = $this->formatDateTime($format, $value);
                }
                break;
            case 'json':
                $value = json_decode($value, true);
                break;
            case 'array':
                $value = empty($value) ? [] : json_decode($value, true);
                break;
            case 'object':
                $value = empty($value) ? new \stdClass() : json_decode($value);
                break;
            case 'serialize':
                try {
                    $value = unserialize($value);
                } catch (\Exception $e) {
                    $value = null;
                }
                break;
            default:
                if (false !== strpos($type, '\\')) {
                    // ��������
                    $value = new $type($value);
                }
        }

        return $value;
    }

    /**
     * ���������ֶλ�ȡ��
     * @access public
     * @param  string|array $name       �ֶ���
     * @param  callable     $callback   �հ���ȡ��
     * @return $this
     */
    public function withAttribute($name, callable $callback = null)
    {
        if (is_array($name)) {
            foreach ($name as $key => $val) {
                $this->withAttribute($key, $val);
            }
        } else {
            $name = $this->getRealFieldName($name);

            if (strpos($name, '.')) {
                list($name, $key) = explode('.', $name);

                $this->withAttr[$name][$key] = $callback;
            } else {
                $this->withAttr[$name] = $callback;
            }
        }

        return $this;
    }

}
