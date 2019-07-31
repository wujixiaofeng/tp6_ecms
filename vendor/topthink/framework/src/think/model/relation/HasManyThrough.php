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
use think\Model;
use think\model\Relation;

/**
 * Զ��һ�Զ������
 */
class HasManyThrough extends Relation
{
    /**
     * �м���������
     * @var string
     */
    protected $throughKey;

    /**
     * �м�����
     * @var string
     */
    protected $throughPk;

    /**
     * �м���ѯ����
     * @var Query
     */
    protected $through;

    /**
     * �ܹ�����
     * @access public
     * @param  Model  $parent     �ϼ�ģ�Ͷ���
     * @param  string $model      ����ģ����
     * @param  string $through    �м�ģ����
     * @param  string $foreignKey �������
     * @param  string $throughKey �м�������
     * @param  string $localKey   ��ǰģ������
     * @param  string $throughPk  �м�ģ������
     */
    public function __construct(Model $parent, string $model, string $through, string $foreignKey, string $throughKey, string $localKey, string $throughPk)
    {
        $this->parent     = $parent;
        $this->model      = $model;
        $this->through    = (new $through)->db();
        $this->foreignKey = $foreignKey;
        $this->throughKey = $throughKey;
        $this->localKey   = $localKey;
        $this->throughPk  = $throughPk;
        $this->query      = (new $model)->db();
    }

    /**
     * �ӳٻ�ȡ��������
     * @access public
     * @param  array   $subRelation �ӹ�����
     * @param  Closure $closure     �հ���ѯ����
     * @return Collection
     */
    public function getRelation(array $subRelation = [], Closure $closure = null)
    {
        if ($closure) {
            $closure($this);
        }

        $this->baseQuery();

        if ($this->withLimit) {
            $this->query->limit($this->withLimit);
        }

        return $this->query->relation($subRelation)
            ->select()
            ->setParent(clone $this->parent);
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
    public function has(string $operator = '>=', int $count = 1, string $id = '*', string $joinType = ''): Query
    {
        $model         = App::parseName(App::classBaseName($this->parent));
        $throughTable  = $this->through->getTable();
        $pk            = $this->throughPk;
        $throughKey    = $this->throughKey;
        $relation      = new $this->model;
        $relationTable = $relation->getTable();

        if ('*' != $id) {
            $id = $relationTable . '.' . $relation->getPk();
        }

        return $this->parent->db()
            ->alias($model)
            ->field($model . '.*')
            ->join($throughTable, $throughTable . '.' . $this->foreignKey . '=' . $model . '.' . $this->localKey)
            ->join($relationTable, $relationTable . '.' . $throughKey . '=' . $throughTable . '.' . $this->throughPk)
            ->group($relationTable . '.' . $this->throughKey)
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
    public function hasWhere($where = [], $fields = null, $joinType = ''): Query
    {
        $model        = App::parseName(App::classBaseName($this->parent));
        $throughTable = $this->through->getTable();
        $pk           = $this->throughPk;
        $throughKey   = $this->throughKey;
        $modelTable   = (new $this->model)->getTable();

        if (is_array($where)) {
            $this->getQueryWhere($where, $modelTable);
        } elseif ($where instanceof Query) {
            $where->via($modelTable);
        }

        $fields = $this->getRelationQueryFields($fields, $model);

        return $this->parent->db()
            ->alias($model)
            ->join($throughTable, $throughTable . '.' . $this->foreignKey . '=' . $model . '.' . $this->localKey)
            ->join($modelTable, $modelTable . '.' . $throughKey . '=' . $throughTable . '.' . $this->throughPk)
            ->group($modelTable . '.' . $this->throughKey)
            ->where($where)
            ->field($fields);
    }

    /**
     * Ԥ���������ѯ�����ݼ���
     * @access protected
     * @param  array   $resultSet   ���ݼ�
     * @param  string  $relation    ��ǰ������
     * @param  array   $subRelation �ӹ�����
     * @param  Closure $closure     �հ�
     * @return void
     */
    public function eagerlyResultSet(array &$resultSet, string $relation, array $subRelation = [], Closure $closure = null): void
    {
        $localKey   = $this->localKey;
        $foreignKey = $this->foreignKey;

        $range = [];
        foreach ($resultSet as $result) {
            // ��ȡ��������б�
            if (isset($result->$localKey)) {
                $range[] = $result->$localKey;
            }
        }

        if (!empty($range)) {
            $this->query->removeWhereField($foreignKey);

            $data = $this->eagerlyWhere([
                [$this->foreignKey, 'in', $range],
            ], $foreignKey, $relation, $subRelation, $closure);

            // ����������
            $attr = App::parseName($relation);

            // �������ݷ�װ
            foreach ($resultSet as $result) {
                $pk = $result->$localKey;
                if (!isset($data[$pk])) {
                    $data[$pk] = [];
                }

                // ���ù�������
                $result->setRelation($attr, $this->resultSetBuild($data[$pk], clone $this->parent));
            }
        }
    }

    /**
     * Ԥ���������ѯ�����ݣ�
     * @access protected
     * @param  Model   $result      ���ݶ���
     * @param  string  $relation    ��ǰ������
     * @param  array   $subRelation �ӹ�����
     * @param  Closure $closure     �հ�
     * @return void
     */
    public function eagerlyResult(Model $result, string $relation, array $subRelation = [], Closure $closure = null): void
    {
        $localKey   = $this->localKey;
        $foreignKey = $this->foreignKey;
        $pk         = $result->$localKey;

        $this->query->removeWhereField($foreignKey);

        $data = $this->eagerlyWhere([
            [$foreignKey, '=', $pk],
        ], $foreignKey, $relation, $subRelation, $closure);

        // �������ݷ�װ
        if (!isset($data[$pk])) {
            $data[$pk] = [];
        }

        $result->setRelation(App::parseName($relation), $this->resultSetBuild($data[$pk], clone $this->parent));
    }

    /**
     * ����ģ��Ԥ��ѯ
     * @access public
     * @param  array   $where       ����Ԥ��ѯ����
     * @param  string  $key         ��������
     * @param  string  $relation    ������
     * @param  array   $subRelation �ӹ���
     * @param  Closure $closure
     * @return array
     */
    protected function eagerlyWhere(array $where, string $key, string $relation, array $subRelation = [], Closure $closure = null): array
    {
        // Ԥ���������ѯ ֧��Ƕ��Ԥ����
        $throughList = $this->through->where($where)->select();
        $keys        = $throughList->column($this->throughPk, $this->throughPk);

        if ($closure) {
            $closure($this);
        }

        $list = $this->query->where($this->throughKey, 'in', $keys)->select();

        // ��װģ������
        $data = [];
        $keys = $throughList->column($this->foreignKey, $this->throughPk);

        foreach ($list as $set) {
            $key = $keys[$set->{$this->throughKey}];

            if ($this->withLimit && isset($data[$key]) && count($data[$key]) >= $this->withLimit) {
                continue;
            }

            $data[$key][] = $set;
        }

        return $data;
    }

    /**
     * ����ͳ��
     * @access public
     * @param  Model   $result  ���ݶ���
     * @param  Closure $closure �հ�
     * @param  string  $aggregate �ۺϲ�ѯ����
     * @param  string  $field �ֶ�
     * @param  string  $name ͳ���ֶα���
     * @return mixed
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

        $alias        = App::parseName(App::classBaseName($this->model));
        $throughTable = $this->through->getTable();
        $pk           = $this->throughPk;
        $throughKey   = $this->throughKey;
        $modelTable   = $this->parent->getTable();

        if (false === strpos($field, '.')) {
            $field = $alias . '.' . $field;
        }

        return $this->query
            ->alias($alias)
            ->join($throughTable, $throughTable . '.' . $pk . '=' . $alias . '.' . $throughKey)
            ->join($modelTable, $modelTable . '.' . $this->localKey . '=' . $throughTable . '.' . $this->foreignKey)
            ->where($throughTable . '.' . $this->foreignKey, $result->$localKey)
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

        $alias        = App::parseName(App::classBaseName($this->model));
        $throughTable = $this->through->getTable();
        $pk           = $this->throughPk;
        $throughKey   = $this->throughKey;
        $modelTable   = $this->parent->getTable();

        if (false === strpos($field, '.')) {
            $field = $alias . '.' . $field;
        }

        return $this->query
            ->alias($alias)
            ->join($throughTable, $throughTable . '.' . $pk . '=' . $alias . '.' . $throughKey)
            ->join($modelTable, $modelTable . '.' . $this->localKey . '=' . $throughTable . '.' . $this->foreignKey)
            ->whereExp($throughTable . '.' . $this->foreignKey, '=' . $this->parent->getTable() . '.' . $this->localKey)
            ->fetchSql()
            ->$aggregate($field);
    }

    /**
     * ִ�л�����ѯ����ִ��һ�Σ�
     * @access protected
     * @return void
     */
    protected function baseQuery(): void
    {
        if (empty($this->baseQuery) && $this->parent->getData()) {
            $alias        = App::parseName(App::classBaseName($this->model));
            $throughTable = $this->through->getTable();
            $pk           = $this->throughPk;
            $throughKey   = $this->throughKey;
            $modelTable   = $this->parent->getTable();
            $fields       = $this->getQueryFields($alias);

            $this->query
                ->field($fields)
                ->alias($alias)
                ->join($throughTable, $throughTable . '.' . $pk . '=' . $alias . '.' . $throughKey)
                ->join($modelTable, $modelTable . '.' . $this->localKey . '=' . $throughTable . '.' . $this->foreignKey)
                ->where($throughTable . '.' . $this->foreignKey, $this->parent->{$this->localKey});

            $this->baseQuery = true;
        }
    }

}
