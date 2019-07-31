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

namespace think\model\relation;

use Closure;
use think\App;
use think\Collection;
use think\db\Query;
use think\db\Raw;
use think\Exception;
use think\Model;
use think\model\Pivot;
use think\model\Relation;
use think\Paginator;

/**
 * ��Զ������
 */
class BelongsToMany extends Relation
{
    /**
     * �м�����
     * @var string
     */
    protected $middle;

    /**
     * �м��ģ������
     * @var string
     */
    protected $pivotName;

    /**
     * �м��ģ�Ͷ���
     * @var Pivot
     */
    protected $pivot;

    /**
     * �м����������
     * @var string
     */
    protected $pivotDataName = 'pivot';

    /**
     * �ܹ�����
     * @access public
     * @param  Model  $parent     �ϼ�ģ�Ͷ���
     * @param  string $model      ģ����
     * @param  string $table      �м����
     * @param  string $foreignKey ����ģ�����
     * @param  string $localKey   ��ǰģ�͹�����
     */
    public function __construct(Model $parent, string $model, string $table, string $foreignKey, string $localKey)
    {
        $this->parent     = $parent;
        $this->model      = $model;
        $this->foreignKey = $foreignKey;
        $this->localKey   = $localKey;

        if (false !== strpos($table, '\\')) {
            $this->pivotName = $table;
            $this->middle    = App::classBaseName($table);
        } else {
            $this->middle = $table;
        }

        $this->query = (new $model)->db();
        $this->pivot = $this->newPivot();
    }

    /**
     * �����м��ģ��
     * @access public
     * @param  $pivot
     * @return $this
     */
    public function pivot(string $pivot)
    {
        $this->pivotName = $pivot;
        return $this;
    }

    /**
     * �����м����������
     * @access public
     * @param  string $name
     * @return $this
     */
    public function name(string $name)
    {
        $this->pivotDataName = $name;
        return $this;
    }

    /**
     * ʵ�����м��ģ��
     * @access public
     * @param  $data
     * @return Pivot
     * @throws Exception
     */
    protected function newPivot(array $data = []): Pivot
    {
        $class = $this->pivotName ?: '\\think\\model\\Pivot';
        $pivot = new $class($data, $this->parent, $this->middle);

        if ($pivot instanceof Pivot) {
            return $pivot;
        } else {
            throw new Exception('pivot model must extends: \think\model\Pivot');
        }
    }

    /**
     * �ϳ��м��ģ��
     * @access protected
     * @param  array|Collection|Paginator $models
     */
    protected function hydratePivot(iterable $models)
    {
        foreach ($models as $model) {
            $pivot = [];

            foreach ($model->getData() as $key => $val) {
                if (strpos($key, '__')) {
                    list($name, $attr) = explode('__', $key, 2);

                    if ('pivot' == $name) {
                        $pivot[$attr] = $val;
                        unset($model->$key);
                    }
                }
            }

            $model->setRelation($this->pivotDataName, $this->newPivot($pivot));
        }
    }

    /**
     * ����������ѯQuery����
     * @access protected
     * @return Query
     */
    protected function buildQuery(): Query
    {
        $foreignKey = $this->foreignKey;
        $localKey   = $this->localKey;

        // ������ѯ
        $pk = $this->parent->getPk();

        $condition = ['pivot.' . $localKey, '=', $this->parent->$pk];

        return $this->belongsToManyQuery($foreignKey, $localKey, [$condition]);
    }

    /**
     * �ӳٻ�ȡ��������
     * @access public
     * @param  array    $subRelation �ӹ�����
     * @param  Closure  $closure     �հ���ѯ����
     * @return Collection
     */
    public function getRelation(array $subRelation = [], Closure $closure = null): Collection
    {
        if ($closure) {
            $closure($this);
        }

        $result = $this->buildQuery()
            ->relation($subRelation)
            ->select()
            ->setParent(clone $this->parent);

        $this->hydratePivot($result);

        return $result;
    }

    /**
     * ����select����
     * @access public
     * @param  mixed $data
     * @return Collection
     */
    public function select($data = null): Collection
    {
        $result = $this->buildQuery()->select($data);
        $this->hydratePivot($result);

        return $result;
    }

    /**
     * ����paginate����
     * @access public
     * @param  int|array $listRows
     * @param  int|bool  $simple
     * @param  array     $config
     * @return Paginator
     */
    public function paginate($listRows = null, $simple = false, $config = []): Paginator
    {
        $result = $this->buildQuery()->paginate($listRows, $simple, $config);
        $this->hydratePivot($result);

        return $result;
    }

    /**
     * ����find����
     * @access public
     * @param  mixed $data
     * @return Model
     */
    public function find($data = null)
    {
        $result = $this->buildQuery()->find($data);

        if (!$result->isEmpty()) {
            $this->hydratePivot([$result]);
        }

        return $result;
    }

    /**
     * ���Ҷ�����¼ ������������׳��쳣
     * @access public
     * @param  array|string|Query|\Closure $data
     * @return Collection
     */
    public function selectOrFail($data = null): Collection
    {
        return $this->buildQuery()->failException(true)->select($data);
    }

    /**
     * ���ҵ�����¼ ������������׳��쳣
     * @access public
     * @param  array|string|Query|\Closure $data
     * @return Model
     */
    public function findOrFail($data = null): Model
    {
        return $this->buildQuery()->failException(true)->find($data);
    }

    /**
     * ���ݹ���������ѯ��ǰģ��
     * @access public
     * @param  string  $operator �Ƚϲ�����
     * @param  integer $count    ����
     * @param  string  $id       �������ͳ���ֶ�
     * @param  string  $joinType JOIN����
     * @return Model
     */
    public function has(string $operator = '>=', $count = 1, $id = '*', string $joinType = 'INNER')
    {
        return $this->parent;
    }

    /**
     * ���ݹ���������ѯ��ǰģ��
     * @access public
     * @param  mixed  $where ��ѯ������������߱հ���
     * @param  mixed  $fields �ֶ�
     * @param  string $joinType JOIN����
     * @return Query
     * @throws Exception
     */
    public function hasWhere($where = [], $fields = null, string $joinType = '')
    {
        throw new Exception('relation not support: hasWhere');
    }

    /**
     * �����м��Ĳ�ѯ����
     * @access public
     * @param  string $field
     * @param  string $op
     * @param  mixed  $condition
     * @return $this
     */
    public function wherePivot($field, $op = null, $condition = null)
    {
        $this->query->where('pivot.' . $field, $op, $condition);
        return $this;
    }

    /**
     * Ԥ���������ѯ�����ݼ���
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
        $pk       = $resultSet[0]->getPk();
        $range    = [];

        foreach ($resultSet as $result) {
            // ��ȡ��������б�
            if (isset($result->$pk)) {
                $range[] = $result->$pk;
            }
        }

        if (!empty($range)) {
            // ��ѯ��������
            $data = $this->eagerlyManyToMany([
                ['pivot.' . $localKey, 'in', $range],
            ], $relation, $subRelation, $closure);

            // ����������
            $attr = App::parseName($relation);

            // �������ݷ�װ
            foreach ($resultSet as $result) {
                if (!isset($data[$result->$pk])) {
                    $data[$result->$pk] = [];
                }

                $result->setRelation($attr, $this->resultSetBuild($data[$result->$pk], clone $this->parent));
            }
        }
    }

    /**
     * Ԥ���������ѯ���������ݣ�
     * @access public
     * @param  Model   $result      ���ݶ���
     * @param  string  $relation    ��ǰ������
     * @param  array   $subRelation �ӹ�����
     * @param  Closure $closure     �հ�
     * @return void
     */
    public function eagerlyResult(Model $result, string $relation, array $subRelation, Closure $closure = null): void
    {
        $pk = $result->getPk();

        if (isset($result->$pk)) {
            $pk = $result->$pk;
            // ��ѯ��������
            $data = $this->eagerlyManyToMany([
                ['pivot.' . $this->localKey, '=', $pk],
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
    public function relationCount(Model $result, Closure $closure = null, string $aggregate = 'count', string $field = '*', string &$name = null): float
    {
        $pk = $result->getPk();

        if (!isset($result->$pk)) {
            return 0;
        }

        $pk = $result->$pk;

        if ($closure) {
            $closure($this, $name);
        }

        return $this->belongsToManyQuery($this->foreignKey, $this->localKey, [
            ['pivot.' . $this->localKey, '=', $pk],
        ])->$aggregate($field);
    }

    /**
     * ��ȡ����ͳ���Ӳ�ѯ
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

        return $this->belongsToManyQuery($this->foreignKey, $this->localKey, [
            [
                'pivot.' . $this->localKey, 'exp', new Raw('=' . $this->parent->db(false)->getTable() . '.' . $this->parent->getPk()),
            ],
        ])->fetchSql()->$aggregate($field);
    }

    /**
     * ��Զ� ����ģ��Ԥ��ѯ
     * @access protected
     * @param  array   $where       ����Ԥ��ѯ����
     * @param  string  $relation    ������
     * @param  array   $subRelation �ӹ���
     * @param  Closure $closure     �հ�
     * @return array
     */
    protected function eagerlyManyToMany(array $where, string $relation, array $subRelation = [], Closure $closure = null): array
    {
        if ($closure) {
            $closure($this);
        }

        // Ԥ���������ѯ ֧��Ƕ��Ԥ����
        $list = $this->belongsToManyQuery($this->foreignKey, $this->localKey, $where)
            ->with($subRelation)
            ->select();

        // ��װģ������
        $data = [];
        foreach ($list as $set) {
            $pivot = [];
            foreach ($set->getData() as $key => $val) {
                if (strpos($key, '__')) {
                    list($name, $attr) = explode('__', $key, 2);
                    if ('pivot' == $name) {
                        $pivot[$attr] = $val;
                        unset($set->$key);
                    }
                }
            }

            $key = $pivot[$this->localKey];

            if ($this->withLimit && isset($data[$key]) && count($data[$key]) >= $this->withLimit) {
                continue;
            }

            $set->setRelation($this->pivotDataName, $this->newPivot($pivot));

            $data[$key][] = $set;
        }

        return $data;
    }

    /**
     * BELONGS TO MANY ������ѯ
     * @access protected
     * @param  string $foreignKey ����ģ�͹�����
     * @param  string $localKey   ��ǰģ�͹�����
     * @param  array  $condition  ������ѯ����
     * @return Query
     */
    protected function belongsToManyQuery(string $foreignKey, string $localKey, array $condition = []): Query
    {
        // ������ѯ��װ
        $tableName = $this->query->getTable();
        $table     = $this->pivot->db()->getTable();
        $fields    = $this->getQueryFields($tableName);

        if ($this->withLimit) {
            $this->query->limit($this->withLimit);
        }

        $query = $this->query
            ->field($fields)
            ->tableField(true, $table, 'pivot', 'pivot__');

        if (empty($this->baseQuery)) {
            $relationFk = $this->query->getPk();
            $query->join([$table => 'pivot'], 'pivot.' . $foreignKey . '=' . $tableName . '.' . $relationFk)
                ->where($condition);
        }

        return $query;
    }

    /**
     * ���棨��������ǰ�������ݶ���
     * @access public
     * @param  mixed $data  ���� ����ʹ������ ����ģ�Ͷ��� �� �������������
     * @param  array $pivot �м���������
     * @return array|Pivot
     */
    public function save($data, array $pivot = [])
    {
        // ���������/�м������
        return $this->attach($data, $pivot);
    }

    /**
     * �������浱ǰ�������ݶ���
     * @access public
     * @param  iterable $dataSet   ���ݼ�
     * @param  array    $pivot     �м���������
     * @param  bool     $samePivot ���������Ƿ���ͬ
     * @return array|false
     */
    public function saveAll(iterable $dataSet, array $pivot = [], bool $samePivot = false)
    {
        $result = [];

        foreach ($dataSet as $key => $data) {
            if (!$samePivot) {
                $pivotData = $pivot[$key] ?? [];
            } else {
                $pivotData = $pivot;
            }

            $result[] = $this->attach($data, $pivotData);
        }

        return empty($result) ? false : $result;
    }

    /**
     * ���ӹ�����һ���м������
     * @access public
     * @param  mixed $data  ���� ����ʹ�����顢����ģ�Ͷ��� ���� �������������
     * @param  array $pivot �м���������
     * @return array|Pivot
     * @throws Exception
     */
    public function attach($data, array $pivot = [])
    {
        if (is_array($data)) {
            if (key($data) === 0) {
                $id = $data;
            } else {
                // �������������
                $model = new $this->model;
                $id    = $model->insertGetId($data);
            }
        } elseif (is_numeric($data) || is_string($data)) {
            // ���ݹ���������ֱ��д���м��
            $id = $data;
        } elseif ($data instanceof Model) {
            // ���ݹ���������ֱ��д���м��
            $relationFk = $data->getPk();
            $id         = $data->$relationFk;
        }

        if (!empty($id)) {
            // �����м������
            $pk                     = $this->parent->getPk();
            $pivot[$this->localKey] = $this->parent->$pk;
            $ids                    = (array) $id;

            foreach ($ids as $id) {
                $pivot[$this->foreignKey] = $id;
                $this->pivot->replace()
                    ->exists(false)
                    ->data([])
                    ->save($pivot);
                $result[] = $this->newPivot($pivot);
            }

            if (count($result) == 1) {
                // �����м��ģ�Ͷ���
                $result = $result[0];
            }

            return $result;
        } else {
            throw new Exception('miss relation data');
        }
    }

    /**
     * �ж��Ƿ���ڹ�������
     * @access public
     * @param  mixed $data ���� ����ʹ�ù���ģ�Ͷ��� ���� �������������
     * @return Pivot|false
     */
    public function attached($data)
    {
        if ($data instanceof Model) {
            $id = $data->getKey();
        } else {
            $id = $data;
        }

        $pivot = $this->pivot
            ->where($this->localKey, $this->parent->getKey())
            ->where($this->foreignKey, $id)
            ->find();

        return $pivot ?: false;
    }

    /**
     * ���������һ���м������
     * @access public
     * @param  integer|array $data        ���� ����ʹ�ù������������
     * @param  bool          $relationDel �Ƿ�ͬʱɾ������������
     * @return integer
     */
    public function detach($data = null, bool $relationDel = false): int
    {
        if (is_array($data)) {
            $id = $data;
        } elseif (is_numeric($data) || is_string($data)) {
            // ���ݹ���������ֱ��д���м��
            $id = $data;
        } elseif ($data instanceof Model) {
            // ���ݹ���������ֱ��д���м��
            $relationFk = $data->getPk();
            $id         = $data->$relationFk;
        }

        // ɾ���м������
        $pk      = $this->parent->getPk();
        $pivot   = [];
        $pivot[] = [$this->localKey, '=', $this->parent->$pk];

        if (isset($id)) {
            $pivot[] = [$this->foreignKey, is_array($id) ? 'in' : '=', $id];
        }

        $result = $this->pivot->where($pivot)->delete();

        // ɾ������������
        if (isset($id) && $relationDel) {
            $model = $this->model;
            $model::destroy($id);
        }

        return $result;
    }

    /**
     * ����ͬ��
     * @access public
     * @param  array $ids
     * @param  bool  $detaching
     * @return array
     */
    public function sync(array $ids, bool $detaching = true): array
    {
        $changes = [
            'attached' => [],
            'detached' => [],
            'updated'  => [],
        ];

        $pk = $this->parent->getPk();

        $current = $this->pivot
            ->where($this->localKey, $this->parent->$pk)
            ->column($this->foreignKey);

        $records = [];

        foreach ($ids as $key => $value) {
            if (!is_array($value)) {
                $records[$value] = [];
            } else {
                $records[$key] = $value;
            }
        }

        $detach = array_diff($current, array_keys($records));

        if ($detaching && count($detach) > 0) {
            $this->detach($detach);
            $changes['detached'] = $detach;
        }

        foreach ($records as $id => $attributes) {
            if (!in_array($id, $current)) {
                $this->attach($id, $attributes);
                $changes['attached'][] = $id;
            } elseif (count($attributes) > 0 && $this->attach($id, $attributes)) {
                $changes['updated'][] = $id;
            }
        }

        return $changes;
    }

    /**
     * ִ�л�����ѯ����ִ��һ�Σ�
     * @access protected
     * @return void
     */
    protected function baseQuery(): void
    {
        if (empty($this->baseQuery) && $this->parent->getData()) {
            $pk    = $this->parent->getPk();
            $table = $this->pivot->getTable();

            $this->query
                ->join([$table => 'pivot'], 'pivot.' . $this->foreignKey . '=' . $this->query->getTable() . '.' . $this->query->getPk())
                ->where('pivot.' . $this->localKey, $this->parent->$pk);
            $this->baseQuery = true;
        }
    }

}
