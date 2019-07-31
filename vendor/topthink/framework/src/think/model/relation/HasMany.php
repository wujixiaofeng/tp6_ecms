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

namespace think\model\relation;

use Closure;
use think\App;
use think\Collection;
use think\db\Query;
use think\Model;
use think\model\Relation;

/**
 * һ�Զ������
 */
class HasMany extends Relation
{
    /**
     * �ܹ�����
     * @access public
     * @param  Model  $parent     �ϼ�ģ�Ͷ���
     * @param  string $model      ģ����
     * @param  string $foreignKey �������
     * @param  string $localKey   ��ǰģ������
     */
    public function __construct(Model $parent, string $model, string $foreignKey, string $localKey)
    {
        $this->parent     = $parent;
        $this->model      = $model;
        $this->foreignKey = $foreignKey;
        $this->localKey   = $localKey;
        $this->query      = (new $model)->db();

        if (get_class($parent) == $model) {
            $this->selfRelation = true;
        }
    }

    /**
     * �ӳٻ�ȡ��������
     * @access public
     * @param  array   $subRelation �ӹ�����
     * @param  Closure $closure     �հ���ѯ����
     * @return Collection
     */
    public function getRelation(array $subRelation = [], Closure $closure = null): Collection
    {
        if ($closure) {
            $closure($this);
        }

        if ($this->withLimit) {
            $this->query->limit($this->withLimit);
        }

        return $this->query
            ->where($this->foreignKey, $this->parent->{$this->localKey})
            ->relation($subRelation)
            ->select()
            ->setParent(clone $this->parent);
    }

    /**
     * Ԥ���������ѯ
     * @access public
     * @param  array   $resultSet   ���ݼ�
     * @param  string  $relation    ��ǰ������
     * @param  array   $subRelation �ӹ�����
     * @param  Closure $closure     �հ�
     * @return void
     */
    public function eagerlyResultSet(array &$resultSet, string $relation, array $subRelation, Closure $closure = null): void
    {
        $localKey = $this->localKey;
        $range    = [];

        foreach ($resultSet as $result) {
            // ��ȡ��������б�
            if (isset($result->$localKey)) {
                $range[] = $result->$localKey;
            }
        }

        if (!empty($range)) {
            $data = $this->eagerlyOneToMany([
                [$this->foreignKey, 'in', $range],
            ], $relation, $subRelation, $closure);

            // ����������
            $attr = App::parseName($relation);

            // �������ݷ�װ
            foreach ($resultSet as $result) {
                $pk = $result->$localKey;
                if (!isset($data[$pk])) {
                    $data[$pk] = [];
                }

                $result->setRelation($attr, $this->resultSetBuild($data[$pk], clone $this->parent));
            }
        }
    }

    /**
     * Ԥ���������ѯ
     * @access public
     * @param  Model   $result      ���ݶ���
     * @param  string  $relation    ��ǰ������
     * @param  array   $subRelation �ӹ�����
     * @param  Closure $closure     �հ�
     * @return void
     */
    public function eagerlyResult(Model $result, string $relation, array $subRelation = [], Closure $closure = null): void
    {
        $localKey = $this->localKey;

        if (isset($result->$localKey)) {
            $pk   = $result->$localKey;
            $data = $this->eagerlyOneToMany([
                [$this->foreignKey, '=', $pk],
            ], $relation, $subRelation, $closure);

            // �������ݷ�װ
            if (!isset($data[$pk])) {
                $data[$pk] = [];
            }

            $result->setRelation(App::parseName($relation), $this->resultSetBuild($data[$pk], clone $this->parent));
        }
    }

    /**
     * ����ͳ��
     * @access public
     * @param  Model   $result  ���ݶ���
     * @param  Closure $closure �հ�
     * @param  string  $aggregate �ۺϲ�ѯ����
     * @param  string  $field �ֶ�
     * @param  string  $name ͳ���ֶα���
     * @return integer
     */
    public function relationCount(Model $result, Closure $closure = null, string $aggregate = 'count', string $field = '*', string &$name = null)
    {
        $localKey = $this->localKey;

        if (!isset($result->$localKey)) {
            return 0;
        }

        if ($closure) {
            $closure($this, $name);
        }

        return $this->query
            ->where($this->foreignKey, '=', $result->$localKey)
            ->$aggregate($field);
    }

    /**
     * ��������ͳ���Ӳ�ѯ
     * @access public
     * @param  Closure $closure �հ�
     * @param  string  $aggregate �ۺϲ�ѯ����
     * @param  string  $field �ֶ�
     * @param  string  $name ͳ���ֶα���
     * @return string
     */
    public function getRelationCountQuery(Closure $closure = null, string $aggregate = 'count', string $field = '*', string &$name = null): string
    {
        if ($closure) {
            $closure($this, $name);
        }

        return $this->query->alias($aggregate . '_table')
            ->whereExp($aggregate . '_table.' . $this->foreignKey, '=' . $this->parent->getTable() . '.' . $this->localKey)
            ->fetchSql()
            ->$aggregate($field);
    }

    /**
     * һ�Զ� ����ģ��Ԥ��ѯ
     * @access public
     * @param  array   $where       ����Ԥ��ѯ����
     * @param  string  $relation    ������
     * @param  array   $subRelation �ӹ���
     * @param  Closure $closure
     * @return array
     */
    protected function eagerlyOneToMany(array $where, string $relation, array $subRelation = [], Closure $closure = null): array
    {
        $foreignKey = $this->foreignKey;

        $this->query->removeWhereField($this->foreignKey);

        // Ԥ���������ѯ ֧��Ƕ��Ԥ����
        if ($closure) {
            $closure($this);
        }

        $list = $this->query->where($where)->with($subRelation)->select();

        // ��װģ������
        $data = [];

        foreach ($list as $set) {
            $key = $set->$foreignKey;

            if ($this->withLimit && isset($data[$key]) && count($data[$key]) >= $this->withLimit) {
                continue;
            }

            $data[$key][] = $set;
        }

        return $data;
    }

    /**
     * ���棨��������ǰ�������ݶ���
     * @access public
     * @param  mixed   $data ���� ����ʹ������ ����ģ�Ͷ���
     * @param  boolean $replace �Ƿ��Զ�ʶ����º�д��
     * @return Model|false
     */
    public function save($data, bool $replace = true)
    {
        $model = $this->make();

        return $model->replace($replace)->save($data) ? $model : false;
    }

    /**
     * ������������ʵ��
     * @param array|Model $data
     * @return Model
     */
    public function make($data = []): Model
    {
        if ($data instanceof Model) {
            $data = $data->getData();
        }

        // �������������
        $data[$this->foreignKey] = $this->parent->{$this->localKey};

        return new $this->model($data);
    }

    /**
     * �������浱ǰ�������ݶ���
     * @access public
     * @param  iterable $dataSet ���ݼ�
     * @param  boolean  $replace �Ƿ��Զ�ʶ����º�д��
     * @return array|false
     */
    public function saveAll(iterable $dataSet, bool $replace = true)
    {
        $result = [];

        foreach ($dataSet as $key => $data) {
            $result[] = $this->save($data, $replace);
        }

        return empty($result) ? false : $result;
    }

    /**
     * ���ݹ���������ѯ��ǰģ��
     * @access public
     * @param  string  $operator �Ƚϲ�����
     * @param  integer $count    ����
     * @param  string  $id       �������ͳ���ֶ�
     * @param  string  $joinType JOIN����
     * @return Query
     */
    public function has(string $operator = '>=', int $count = 1, string $id = '*', string $joinType = 'INNER'): Query
    {
        $table = $this->query->getTable();

        $model    = App::classBaseName($this->parent);
        $relation = App::classBaseName($this->model);

        if ('*' != $id) {
            $id = $relation . '.' . (new $this->model)->getPk();
        }

        return $this->parent->db()
            ->alias($model)
            ->field($model . '.*')
            ->join([$table => $relation], $model . '.' . $this->localKey . '=' . $relation . '.' . $this->foreignKey, $joinType)
            ->group($relation . '.' . $this->foreignKey)
            ->having('count(' . $id . ')' . $operator . $count);
    }

    /**
     * ���ݹ���������ѯ��ǰģ��
     * @access public
     * @param  mixed  $where ��ѯ������������߱հ���
     * @param  mixed  $fields �ֶ�
     * @param  string $joinType JOIN����
     * @return Query
     */
    public function hasWhere($where = [], $fields = null, string $joinType = ''): Query
    {
        $table    = $this->query->getTable();
        $model    = App::classBaseName($this->parent);
        $relation = App::classBaseName($this->model);

        if (is_array($where)) {
            $this->getQueryWhere($where, $relation);
        } elseif ($where instanceof Query) {
            $where->via($relation);
        }

        $fields = $this->getRelationQueryFields($fields, $model);

        return $this->parent->db()
            ->alias($model)
            ->group($model . '.' . $this->localKey)
            ->field($fields)
            ->join([$table => $relation], $model . '.' . $this->localKey . '=' . $relation . '.' . $this->foreignKey)
            ->where($where);
    }

    /**
     * ִ�л�����ѯ����ִ��һ�Σ�
     * @access protected
     * @return void
     */
    protected function baseQuery(): void
    {
        if (empty($this->baseQuery)) {
            if (isset($this->parent->{$this->localKey})) {
                // ������ѯ�����������
                $this->query->where($this->foreignKey, '=', $this->parent->{$this->localKey});
            }

            $this->baseQuery = true;
        }
    }

}
