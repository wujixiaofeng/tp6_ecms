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
use think\db\Query;
use think\Model;

/**
 * HasOne ������
 */
class HasOne extends OneToOne
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
     * @return Model
     */
    public function getRelation(array $subRelation = [], Closure $closure = null)
    {
        $localKey = $this->localKey;

        if ($closure) {
            $closure($this);
        }

        // �жϹ�������ִ�в�ѯ
        $relationModel = $this->query
            ->removeWhereField($this->foreignKey)
            ->where($this->foreignKey, $this->parent->$localKey)
            ->relation($subRelation)
            ->find();

        if ($relationModel) {
            $relationModel->setParent(clone $this->parent);
        }

        return $relationModel;
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

        return $this->query
            ->whereExp($this->foreignKey, '=' . $this->parent->getTable() . '.' . $this->localKey)
            ->fetchSql()
            ->$aggregate($field);
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
        $table      = $this->query->getTable();
        $model      = App::classBaseName($this->parent);
        $relation   = App::classBaseName($this->model);
        $localKey   = $this->localKey;
        $foreignKey = $this->foreignKey;

        return $this->parent->db()
            ->alias($model)
            ->whereExists(function ($query) use ($table, $model, $relation, $localKey, $foreignKey) {
                $query->table([$table => $relation])
                    ->field($relation . '.' . $foreignKey)
                    ->whereExp($model . '.' . $localKey, '=' . $relation . '.' . $foreignKey);
            });
    }

    /**
     * ���ݹ���������ѯ��ǰģ��
     * @access public
     * @param  mixed  $where ��ѯ������������߱հ���
     * @param  mixed  $fields   �ֶ�
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
            ->field($fields)
            ->join([$table => $relation], $model . '.' . $this->localKey . '=' . $relation . '.' . $this->foreignKey, $joinType ?: $this->joinType)
            ->where($where);
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
    protected function eagerlySet(array &$resultSet, string $relation, array $subRelation = [], Closure $closure = null): void
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
                [$foreignKey, 'in', $range],
            ], $foreignKey, $relation, $subRelation, $closure);

            // ����������
            $attr = App::parseName($relation);

            // �������ݷ�װ
            foreach ($resultSet as $result) {
                // ����ģ��
                if (!isset($data[$result->$localKey])) {
                    $relationModel = null;
                } else {
                    $relationModel = $data[$result->$localKey];
                    $relationModel->setParent(clone $result);
                    $relationModel->exists(true);
                }

                if ($relationModel && !empty($this->bindAttr)) {
                    // �󶨹�������
                    $this->bindAttr($relationModel, $result);
                } else {
                    // ���ù�������
                    $result->setRelation($attr, $relationModel);
                }
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
    protected function eagerlyOne(Model $result, string $relation, array $subRelation = [], Closure $closure = null): void
    {
        $localKey   = $this->localKey;
        $foreignKey = $this->foreignKey;

        $this->query->removeWhereField($foreignKey);

        $data = $this->eagerlyWhere([
            [$foreignKey, '=', $result->$localKey],
        ], $foreignKey, $relation, $subRelation, $closure);

        // ����ģ��
        if (!isset($data[$result->$localKey])) {
            $relationModel = null;
        } else {
            $relationModel = $data[$result->$localKey];
            $relationModel->setParent(clone $result);
            $relationModel->exists(true);
        }

        if ($relationModel && !empty($this->bindAttr)) {
            // �󶨹�������
            $this->bindAttr($relationModel, $result);
        } else {
            $result->setRelation(App::parseName($relation), $relationModel);
        }
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
