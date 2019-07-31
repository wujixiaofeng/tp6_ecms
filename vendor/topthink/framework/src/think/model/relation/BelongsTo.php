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
 * BelongsTo������
 */
class BelongsTo extends OneToOne
{
    /**
     * �ܹ�����
     * @access public
     * @param  Model  $parent �ϼ�ģ�Ͷ���
     * @param  string $model ģ����
     * @param  string $foreignKey �������
     * @param  string $localKey ��������
     * @param  string $relation  ������
     */
    public function __construct(Model $parent, string $model, string $foreignKey, string $localKey, string $relation = null)
    {
        $this->parent     = $parent;
        $this->model      = $model;
        $this->foreignKey = $foreignKey;
        $this->localKey   = $localKey;
        $this->query      = (new $model)->db();
        $this->relation   = $relation;

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
        if ($closure) {
            $closure($this);
        }

        $foreignKey = $this->foreignKey;

        $relationModel = $this->query
            ->removeWhereField($this->localKey)
            ->where($this->localKey, $this->parent->$foreignKey)
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
     * @param  string  $name �ۺ��ֶα���
     * @return string
     */
    public function getRelationCountQuery(Closure $closure = null, string $aggregate = 'count', string $field = '*', &$name = ''): string
    {
        if ($closure) {
            $closure($this, $name);
        }

        return $this->query
            ->whereExp($this->localKey, '=' . $this->parent->getTable() . '.' . $this->foreignKey)
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
        $foreignKey = $this->foreignKey;

        if (!isset($result->$foreignKey)) {
            return 0;
        }

        if ($closure) {
            $closure($this, $name);
        }

        return $this->query
            ->where($this->localKey, '=', $result->$foreignKey)
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
                    ->field($relation . '.' . $localKey)
                    ->whereExp($model . '.' . $foreignKey, '=' . $relation . '.' . $localKey);
            });
    }

    /**
     * ���ݹ���������ѯ��ǰģ��
     * @access public
     * @param  mixed  $where  ��ѯ������������߱հ���
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
            ->field($fields)
            ->join([$table => $relation], $model . '.' . $this->foreignKey . '=' . $relation . '.' . $this->localKey, $joinType ?: $this->joinType)
            ->where($where);
    }

    /**
     * Ԥ���������ѯ�����ݼ���
     * @access protected
     * @param  array   $resultSet ���ݼ�
     * @param  string  $relation ��ǰ������
     * @param  array   $subRelation �ӹ�����
     * @param  Closure $closure �հ�
     * @return void
     */
    protected function eagerlySet(array &$resultSet, string $relation, array $subRelation = [], Closure $closure = null): void
    {
        $localKey   = $this->localKey;
        $foreignKey = $this->foreignKey;

        $range = [];
        foreach ($resultSet as $result) {
            // ��ȡ��������б�
            if (isset($result->$foreignKey)) {
                $range[] = $result->$foreignKey;
            }
        }

        if (!empty($range)) {
            $this->query->removeWhereField($localKey);

            $data = $this->eagerlyWhere([
                [$localKey, 'in', $range],
            ], $localKey, $relation, $subRelation, $closure);

            // ����������
            $attr = App::parseName($relation);

            // �������ݷ�װ
            foreach ($resultSet as $result) {
                // ����ģ��
                if (!isset($data[$result->$foreignKey])) {
                    $relationModel = null;
                } else {
                    $relationModel = $data[$result->$foreignKey];
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
     * @param  Model   $result ���ݶ���
     * @param  string  $relation ��ǰ������
     * @param  array   $subRelation �ӹ�����
     * @param  Closure $closure �հ�
     * @return void
     */
    protected function eagerlyOne(Model $result, string $relation, array $subRelation = [], Closure $closure = null): void
    {
        $localKey   = $this->localKey;
        $foreignKey = $this->foreignKey;

        $this->query->removeWhereField($localKey);

        $data = $this->eagerlyWhere([
            [$localKey, '=', $result->$foreignKey],
        ], $localKey, $relation, $subRelation, $closure);

        // ����ģ��
        if (!isset($data[$result->$foreignKey])) {
            $relationModel = null;
        } else {
            $relationModel = $data[$result->$foreignKey];
            $relationModel->setParent(clone $result);
            $relationModel->exists(true);
        }

        if ($relationModel && !empty($this->bindAttr)) {
            // �󶨹�������
            $this->bindAttr($relationModel, $result);
        } else {
            // ���ù�������
            $result->setRelation(App::parseName($relation), $relationModel);
        }
    }

    /**
     * ��ӹ�������
     * @access public
     * @param  Model $model����ģ�Ͷ���
     * @return Model
     */
    public function associate(Model $model): Model
    {
        $this->parent->setAttr($this->foreignKey, $model->getKey());
        $this->parent->save();

        return $this->parent->setRelation($this->relation, $model);
    }

    /**
     * ע����������
     * @access public
     * @return Model
     */
    public function dissociate(): Model
    {
        $foreignKey = $this->foreignKey;

        $this->parent->setAttr($foreignKey, null);
        $this->parent->save();

        return $this->parent->setRelation($this->relation, null);
    }

    /**
     * ִ�л�����ѯ����ִ��һ�Σ�
     * @access protected
     * @return void
     */
    protected function baseQuery(): void
    {
        if (empty($this->baseQuery)) {
            if (isset($this->parent->{$this->foreignKey})) {
                // ������ѯ�����������
                $this->query->where($this->localKey, '=', $this->parent->{$this->foreignKey});
            }

            $this->baseQuery = true;
        }
    }
}
