<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: zhangyajun <448901948@qq.com>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace think;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;

/**
 * ���ݼ�������
 */
class Collection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    /**
     * ���ݼ�����
     * @var array
     */
    protected $items = [];

    public function __construct($items = [])
    {
        $this->items = $this->convertToArray($items);
    }

    public static function make($items = [])
    {
        return new static($items);
    }

    /**
     * �Ƿ�Ϊ��
     * @access public
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function toArray(): array
    {
        return array_map(function ($value) {
            return ($value instanceof Model || $value instanceof self) ? $value->toArray() : $value;
        }, $this->items);
    }

    public function all(): array
    {
        return $this->items;
    }

    /**
     * �ϲ�����
     *
     * @access public
     * @param  mixed $items ����
     * @return static
     */
    public function merge($items)
    {
        return new static(array_merge($this->items, $this->convertToArray($items)));
    }

    /**
     * ��ָ������������
     *
     * @access public
     * @param  mixed  $items    ����
     * @param  string $indexKey ����
     * @return array
     */
    public function dictionary($items = null, string &$indexKey = null)
    {
        if ($items instanceof self || $items instanceof Paginator) {
            $items = $items->all();
        }

        $items = is_null($items) ? $this->items : $items;

        if ($items && empty($indexKey)) {
            $indexKey = is_array($items[0]) ? 'id' : $items[0]->getPk();
        }

        if (isset($indexKey) && is_string($indexKey)) {
            return array_column($items, null, $indexKey);
        }

        return $items;
    }

    /**
     * �Ƚ����飬���ز
     *
     * @access public
     * @param  mixed  $items    ����
     * @param  string $indexKey ָ���Ƚϵļ���
     * @return static
     */
    public function diff($items, string $indexKey = null)
    {
        if ($this->isEmpty() || is_scalar($this->items[0])) {
            return new static(array_diff($this->items, $this->convertToArray($items)));
        }

        $diff       = [];
        $dictionary = $this->dictionary($items, $indexKey);

        if (is_string($indexKey)) {
            foreach ($this->items as $item) {
                if (!isset($dictionary[$item[$indexKey]])) {
                    $diff[] = $item;
                }
            }
        }

        return new static($diff);
    }

    /**
     * �Ƚ����飬���ؽ���
     *
     * @access public
     * @param  mixed  $items    ����
     * @param  string $indexKey ָ���Ƚϵļ���
     * @return static
     */
    public function intersect($items, string $indexKey = null)
    {
        if ($this->isEmpty() || is_scalar($this->items[0])) {
            return new static(array_diff($this->items, $this->convertToArray($items)));
        }

        $intersect  = [];
        $dictionary = $this->dictionary($items, $indexKey);

        if (is_string($indexKey)) {
            foreach ($this->items as $item) {
                if (isset($dictionary[$item[$indexKey]])) {
                    $intersect[] = $item;
                }
            }
        }

        return new static($intersect);
    }

    /**
     * ���������еļ���ֵ
     *
     * @access public
     * @return static
     */
    public function flip()
    {
        return new static(array_flip($this->items));
    }

    /**
     * �������������еļ���
     *
     * @access public
     * @return static
     */
    public function keys()
    {
        return new static(array_keys($this->items));
    }

    /**
     * �������������е�ֵ��ɵ��� Collection ʵ��
     * @access public
     * @return static
     */
    public function values()
    {
        return new static(array_values($this->items));
    }

    /**
     * ɾ����������һ��Ԫ�أ���ջ��
     *
     * @access public
     * @return mixed
     */
    public function pop()
    {
        return array_pop($this->items);
    }

    /**
     * ͨ��ʹ���û��Զ��庯�������ַ�����������
     *
     * @access public
     * @param  callable $callback ���÷���
     * @param  mixed    $initial
     * @return mixed
     */
    public function reduce(callable $callback, $initial = null)
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * ���෴��˳�򷵻����顣
     *
     * @access public
     * @return static
     */
    public function reverse()
    {
        return new static(array_reverse($this->items));
    }

    /**
     * ɾ���������׸�Ԫ�أ������ر�ɾ��Ԫ�ص�ֵ
     *
     * @access public
     * @return mixed
     */
    public function shift()
    {
        return array_shift($this->items);
    }

    /**
     * �������β����һ��Ԫ��
     * @access public
     * @param  mixed  $value Ԫ��
     * @param  string $key KEY
     * @return void
     */
    public function push($value, string $key = null): void
    {
        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }

    /**
     * ��һ������ָ�Ϊ�µ������.
     *
     * @access public
     * @param  int  $size ���С
     * @param  bool $preserveKeys
     * @return static
     */
    public function chunk(int $size, bool $preserveKeys = false)
    {
        $chunks = [];

        foreach (array_chunk($this->items, $size, $preserveKeys) as $chunk) {
            $chunks[] = new static($chunk);
        }

        return new static($chunks);
    }

    /**
     * �����鿪ͷ����һ��Ԫ��
     * @access public
     * @param mixed  $value Ԫ��
     * @param string $key KEY
     * @return void
     */
    public function unshift($value, string $key = null): void
    {
        if (is_null($key)) {
            array_unshift($this->items, $value);
        } else {
            $this->items = [$key => $value] + $this->items;
        }
    }

    /**
     * ��ÿ��Ԫ��ִ�и��ص�
     *
     * @access public
     * @param  callable $callback �ص�
     * @return $this
     */
    public function each(callable $callback)
    {
        foreach ($this->items as $key => $item) {
            $result = $callback($item, $key);

            if (false === $result) {
                break;
            } elseif (!is_object($item)) {
                $this->items[$key] = $result;
            }
        }

        return $this;
    }

    /**
     * �ûص��������������е�Ԫ��
     * @access public
     * @param  callable|null $callback �ص�
     * @return static
     */
    public function map(callable $callback)
    {
        return new static(array_map($callback, $this->items));
    }

    /**
     * �ûص��������������е�Ԫ��
     * @access public
     * @param  callable|null $callback �ص�
     * @return static
     */
    public function filter(callable $callback = null)
    {
        if ($callback) {
            return new static(array_filter($this->items, $callback));
        }

        return new static(array_filter($this->items));
    }

    /**
     * �����ֶ��������������е�Ԫ��
     * @access public
     * @param  string $field �ֶ���
     * @param  mixed  $operator ������
     * @param  mixed  $value ����
     * @return static
     */
    public function where(string $field, $operator, $value = null)
    {
        if (is_null($value)) {
            $value    = $operator;
            $operator = '=';
        }

        return $this->filter(function ($data) use ($field, $operator, $value) {
            if (strpos($field, '.')) {
                list($field, $relation) = explode('.', $field);

                $result = $data[$field][$relation] ?? null;
            } else {
                $result = $data[$field] ?? null;
            }

            switch ($operator) {
                case '===':
                    return $result === $value;
                case '!==':
                    return $result !== $value;
                case '!=':
                case '<>':
                    return $result != $value;
                case '>':
                    return $result > $value;
                case '>=':
                    return $result >= $value;
                case '<':
                    return $result < $value;
                case '<=':
                    return $result <= $value;
                case 'like':
                    return is_string($result) && false !== strpos($result, $value);
                case 'not like':
                    return is_string($result) && false === strpos($result, $value);
                case 'in':
                    return is_scalar($result) && in_array($result, $value, true);
                case 'not in':
                    return is_scalar($result) && !in_array($result, $value, true);
                case 'between':
                    list($min, $max) = is_string($value) ? explode(',', $value) : $value;
                    return is_scalar($result) && $result >= $min && $result <= $max;
                case 'not between':
                    list($min, $max) = is_string($value) ? explode(',', $value) : $value;
                    return is_scalar($result) && $result > $max || $result < $min;
                case '==':
                case '=':
                default:
                    return $result == $value;
            }
        });
    }

    /**
     * LIKE����
     * @access public
     * @param  string $field �ֶ���
     * @param  string $value ����
     * @return static
     */
    public function whereLike(string $field, string $value)
    {
        return $this->where($field, 'like', $value);
    }

    /**
     * NOT LIKE����
     * @access public
     * @param  string $field �ֶ���
     * @param  string $value ����
     * @return static
     */
    public function whereNotLike(string $field, string $value)
    {
        return $this->where($field, 'not like', $value);
    }

    /**
     * IN����
     * @access public
     * @param  string $field �ֶ���
     * @param  array  $value ����
     * @return static
     */
    public function whereIn(string $field, array $value)
    {
        return $this->where($field, 'in', $value);
    }

    /**
     * NOT IN����
     * @access public
     * @param  string $field �ֶ���
     * @param  array  $value ����
     * @return static
     */
    public function whereNotIn(string $field, array $value)
    {
        return $this->where($field, 'not in', $value);
    }

    /**
     * BETWEEN ����
     * @access public
     * @param  string $field �ֶ���
     * @param  mixed  $value ����
     * @return static
     */
    public function whereBetween(string $field, $value)
    {
        return $this->where($field, 'between', $value);
    }

    /**
     * NOT BETWEEN ����
     * @access public
     * @param  string $field �ֶ���
     * @param  mixed  $value ����
     * @return static
     */
    public function whereNotBetween(string $field, $value)
    {
        return $this->where($field, 'not between', $value);
    }

    /**
     * ����������ָ����һ��
     * @access public
     * @param string $columnKey ����
     * @param string $indexKey  ��Ϊ����ֵ����
     * @return array
     */
    public function column(string $columnKey, string $indexKey = null)
    {
        return array_column($this->items, $columnKey, $indexKey);
    }

    /**
     * ����������
     *
     * @access public
     * @param  callable|null $callback �ص�
     * @return static
     */
    public function sort(callable $callback = null)
    {
        $items = $this->items;

        $callback = $callback ?: function ($a, $b) {
            return $a == $b ? 0 : (($a < $b) ? -1 : 1);

        };

        uasort($items, $callback);

        return new static($items);
    }

    /**
     * ָ���ֶ�����
     * @access public
     * @param  string $field �����ֶ�
     * @param  string $order ����
     * @return $this
     */
    public function order(string $field, string $order = null)
    {
        return $this->sort(function ($a, $b) use ($field, $order) {
            $fieldA = $a[$field] ?? null;
            $fieldB = $b[$field] ?? null;

            return 'desc' == strtolower($order) ? strcmp($fieldB, $fieldA) : strcmp($fieldA, $fieldB);
        });
    }

    /**
     * ���������
     *
     * @access public
     * @return static
     */
    public function shuffle()
    {
        $items = $this->items;

        shuffle($items);

        return new static($items);
    }

    /**
     * ��ȡ����
     *
     * @access public
     * @param  int  $offset ��ʼλ��
     * @param  int  $length ��ȡ����
     * @param  bool $preserveKeys preserveKeys
     * @return static
     */
    public function slice(int $offset, int $length = null, bool $preserveKeys = false)
    {
        return new static(array_slice($this->items, $offset, $length, $preserveKeys));
    }

    // ArrayAccess
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->items);
    }

    public function offsetGet($offset)
    {
        return $this->items[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->items[$offset]);
    }

    //Countable
    public function count()
    {
        return count($this->items);
    }

    //IteratorAggregate
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    //JsonSerializable
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * ת����ǰ���ݼ�ΪJSON�ַ���
     * @access public
     * @param  integer $options json����
     * @return string
     */
    public function toJson(int $options = JSON_UNESCAPED_UNICODE)
    {
        return json_encode($this->toArray(), $options);
    }

    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * ת��������
     *
     * @access public
     * @param  mixed $items ����
     * @return array
     */
    protected function convertToArray($items): array
    {
        if ($items instanceof self) {
            return $items->all();
        }

        return (array) $items;
    }
}
