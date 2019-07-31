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

namespace think\model;

use think\db\Query;
use think\Exception;
use think\Model;

/**
 * ģ�͹���������
 * @package think\model
 *
 * @mixin Query
 */
abstract class Relation
{
    /**
     * ��ģ�Ͷ���
     * @var Model
     */
    protected $parent;

    /**
     * ��ǰ������ģ������
     * @var string
     */
    protected $model;

    /**
     * ����ģ�Ͳ�ѯ����
     * @var Query
     */
    protected $query;

    /**
     * ���������
     * @var string
     */
    protected $foreignKey;

    /**
     * ����������
     * @var string
     */
    protected $localKey;

    /**
     * �Ƿ�ִ�й���������ѯ
     * @var bool
     */
    protected $baseQuery;

    /**
     * �Ƿ�Ϊ�Թ���
     * @var bool
     */
    protected $selfRelation = false;

    /**
     * ����������������
     * @var int
     */
    protected $withLimit;

    /**
     * ���������ֶ�����
     * @var array
     */
    protected $withField;

    /**
     * ��ȡ����������ģ��
     * @access public
     * @return Model
     */
    public function getParent(): Model
    {
        return $this->parent;
    }

    /**
     * ��ȡ��ǰ�Ĺ���ģ�����Queryʵ��
     * @access public
     * @return Query
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * ��ȡ��ǰ�Ĺ���ģ�����ʵ��
     * @access public
     * @param bool $clear �Ƿ���Ҫ��ղ�ѯ����
     * @return Model
     */
    public function getModel(bool $clear = true): Model
    {
        return $this->query->getModel($clear);
    }

    /**
     * ��ǰ�����Ƿ�Ϊ�Թ���
     * @access public
     * @return bool
     */
    public function isSelfRelation(): bool
    {
        return $this->selfRelation;
    }

    /**
     * ��װ�������ݼ�
     * @access public
     * @param  array $resultSet ���ݼ�
     * @param  Model $parent ��ģ��
     * @return mixed
     */
    protected function resultSetBuild(array $resultSet, Model $parent = null)
    {
        return (new $this->model)->toCollection($resultSet)->setParent($parent);
    }

    protected function getQueryFields(string $model)
    {
        $fields = $this->query->getOptions('field');
        return $this->getRelationQueryFields($fields, $model);
    }

    protected function getRelationQueryFields($fields, string $model)
    {
        if (empty($fields) || '*' == $fields) {
            return $model . '.*';
        }

        if (is_string($fields)) {
            $fields = explode(',', $fields);
        }

        foreach ($fields as &$field) {
            if (false === strpos($field, '.')) {
                $field = $model . '.' . $field;
            }
        }

        return $fields;
    }

    protected function getQueryWhere(array &$where, string $relation): void
    {
        foreach ($where as $key => &$val) {
            if (is_string($key)) {
                $where[] = [false === strpos($key, '.') ? $relation . '.' . $key : $key, '=', $val];
                unset($where[$key]);
            } elseif (isset($val[0]) && false === strpos($val[0], '.')) {
                $val[0] = $relation . '.' . $val[0];
            }
        }
    }

    /**
     * ��������
     * @access public
     * @param  array $data ��������
     * @return integer
     */
    public function update(array $data = []): int
    {
        return $this->query->update($data);
    }

    /**
     * ɾ����¼
     * @access public
     * @param  mixed $data ���ʽ true ��ʾǿ��ɾ��
     * @return int
     * @throws Exception
     * @throws PDOException
     */
    public function delete($data = null): int
    {
        return $this->query->delete($data);
    }

    /**
     * ���ƹ������ݵ�����
     * @access public
     * @param  int $limit ������������
     * @return $this
     */
    public function withLimit(int $limit)
    {
        $this->withLimit = $limit;
        return $this;
    }

    /**
     * ���ƹ������ݵ��ֶ�
     * @access public
     * @param  array $field �����ֶ�����
     * @return $this
     */
    public function withField(array $field)
    {
        $this->withField = $field;
        return $this;
    }

    /**
     * ִ�л�����ѯ����ִ��һ�Σ�
     * @access protected
     * @return void
     */
    protected function baseQuery(): void
    {}

    public function __call($method, $args)
    {
        if ($this->query) {
            // ִ�л�����ѯ
            $this->baseQuery();

            $model  = $this->query->getModel(false);
            $result = call_user_func_array([$model, $method], $args);

            $this->query = $model->getQuery();
            return $result === $this->query ? $this : $result;
        }

        throw new Exception('method not exists:' . __CLASS__ . '->' . $method);
    }
}
