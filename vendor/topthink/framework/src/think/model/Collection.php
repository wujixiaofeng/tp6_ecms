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

namespace think\model;

use think\Collection as BaseCollection;
use think\Model;
use think\Paginator;

/**
 * ģ�����ݼ���
 */
class Collection extends BaseCollection
{
    /**
     * �ӳ�Ԥ���������ѯ
     * @access public
     * @param  array|string $relation ����
     * @return $this
     */
    public function load($relation)
    {
        if (!$this->isEmpty()) {
            $item = current($this->items);
            $item->eagerlyResultSet($this->items, (array) $relation);
        }

        return $this;
    }

    /**
     * ɾ�����ݼ�������
     * @access public
     * @return bool
     */
    public function delete(): bool
    {
        $this->each(function (Model $model) {
            $model->delete();
        });

        return true;
    }

    /**
     * ��������
     * @access public
     * @param array $data       ��������
     * @param array $allowField �����ֶ�
     * @return bool
     */
    public function update(array $data, array $allowField = []): bool
    {
        $this->each(function (Model $model) use ($data, $allowField) {
            if (!empty($allowField)) {
                $model->allowField($allowField);
            }

            $model->save($data);
        });

        return true;
    }

    /**
     * ������Ҫ���ص��������
     * @access public
     * @param  array $hidden �����б�
     * @return $this
     */
    public function hidden(array $hidden)
    {
        $this->each(function (Model $model) use ($hidden) {
            $model->hidden($hidden);
        });

        return $this;
    }

    /**
     * ������Ҫ���������
     * @access public
     * @param  array $visible
     * @return $this
     */
    public function visible(array $visible)
    {
        $this->each(function (Model $model) use ($visible) {
            $model->visible($visible);
        });

        return $this;
    }

    /**
     * ������Ҫ׷�ӵ��������
     * @access public
     * @param  array $append �����б�
     * @return $this
     */
    public function append(array $append)
    {
        $this->each(function (Model $model) use ($append) {
            $model->append($append);
        });

        return $this;
    }

    /**
     * ���ø�ģ��
     * @access public
     * @param  Model $parent ��ģ��
     * @return $this
     */
    public function setParent(Model $parent)
    {
        $this->each(function (Model $model) use ($parent) {
            $model->setParent($parent);
        });

        return $this;
    }

    /**
     * ���������ֶλ�ȡ��
     * @access public
     * @param  string|array $name       �ֶ���
     * @param  callable     $callback   �հ���ȡ��
     * @return $this
     */
    public function withAttr($name, $callback = null)
    {
        $this->each(function (Model $model) use ($name, $callback) {
            $model->withAttribute($name, $callback);
        });

        return $this;
    }

    /**
     * �󶨣�һ��һ���������Ե���ǰģ��
     * @access protected
     * @param  string $relation ��������
     * @param  array  $attrs    ������
     * @return $this
     * @throws Exception
     */
    public function bindAttr(string $relation, array $attrs = [])
    {
        $this->each(function (Model $model) use ($relation, $attrs) {
            $model->bindAttr($relation, $attrs);
        });

        return $this;
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
            $indexKey = $items[0]->getPk();
        }

        if (isset($indexKey) && is_string($indexKey)) {
            return array_column($items, null, $indexKey);
        }

        return $items;
    }

    /**
     * �Ƚ����ݼ������ز
     *
     * @access public
     * @param  mixed  $items    ����
     * @param  string $indexKey ָ���Ƚϵļ���
     * @return static
     */
    public function diff($items, string $indexKey = null)
    {
        if ($this->isEmpty()) {
            return new static($items);
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
     * �Ƚ����ݼ������ؽ���
     *
     * @access public
     * @param  mixed  $items    ����
     * @param  string $indexKey ָ���Ƚϵļ���
     * @return static
     */
    public function intersect($items, string $indexKey = null)
    {
        if ($this->isEmpty()) {
            return new static([]);
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
}
