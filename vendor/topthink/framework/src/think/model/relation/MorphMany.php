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
use think\Exception;
use think\Model;
use think\model\Relation;

/**
 * ��̬һ�Զ����
 */
class MorphMany extends Relation
{

    /**
     * ��̬�������
     * @var string
     */
    protected $morphKey;
    /**
     * ��̬�ֶ���
     * @var string
     */
    protected $morphType;

    /**
     * ��̬����
     * @var string
     */
    protected $type;

    /**
     * �ܹ�����
     * @access public
     * @param  Model  $parent    �ϼ�ģ�Ͷ���
     * @param  string $model     ģ����
     * @param  string $morphKey  �������
     * @param  string $morphType ��̬�ֶ���
     * @param  string $type      ��̬����
     */
    public function __construct(Model $parent, string $model, string $morphKey, string $morphType, string $type)
    {
        $this->parent    = $parent;
        $this->model     = $model;
        $this->type      = $type;
        $this->morphKey  = $morphKey;
        $this->morphType = $morphType;
        $this->query     = (new $model)->db();
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
    public function has(string $operator = '>=', int $count = 1, string $id = '*', string $joinType = '')
    {
        throw new Exception('relation not support: has');
    }

    /**
     * ���ݹ���������ѯ��ǰģ��
     * @access public
     * @param  mixed  $where ��ѯ������������߱հ���
     * @param  mixed  $fields �ֶ�
     * @param  string $joinType JOIN����
     * @return Query
     */
    public function hasWhere($where = [], $fields = null, string $joinType = '')
    {
        throw new Exception('relation not support: hasWhere');
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
        $morphType = $this->morphType;
        $morphKey  = $this->morphKey;
        $type      = $this->type;
        $range     = [];

        foreach ($resultSet as $result) {
            $pk = $result->getPk();
            // ��ȡ��������б�
            if (isset($result->$pk)) {
                $range[] = $result->$pk;
            }
        }

        if (!empty($range)) {
            $where = [
                [$morphKey, 'in', $range],
                [$morphType, '=', $type],
            ];
            $data = $this->eagerlyMorphToMany($where, $relation, $subRelation, $closure);

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
        $pk = $result->getPk();

        if (isset($result->$pk)) {
            $key  = $result->$pk;
            $data = $this->eagerlyMorphToMany([
                [$this->morphKey, '=', $key],
                [$this->morphType, '=', $this->type],
            ], $relation, $subRelation, $closure);

            if (!isset($data[$key])) {
                $data[$key] = [];
            }

            $result->setRelation(App::parseName($relation), $this->resultSetBuild($data[$key], clone $this->parent));
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
     * @return mixed
     */
    public function relationCount(Model $result, Closure $closure = null, string $aggregate = 'count', string $field = '*', string &$name = null)
    {
        $pk = $result->getPk();

        if (!isset($result->$pk)) {
            return 0;
        }

        if ($closure) {
            $closure($this, $name);
        }

        return $this->query
            ->where([
                [$this->morphKey, '=', $result->$pk],
                [$this->morphType, '=', $this->type],
            ])
            ->$aggregate($field);
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

        return $this->query
            ->whereExp($this->morphKey, '=' . $this->parent->getTable() . '.' . $this->parent->getPk())
            ->where($this->morphType, '=', $this->type)
            ->fetchSql()
            ->$aggregate($field);
    }

    /**
     * ��̬һ�Զ� ����ģ��Ԥ��ѯ
     * @access protected
     * @param  array   $where       ����Ԥ��ѯ����
     * @param  string  $relation    ������
     * @param  array   $subRelation �ӹ���
     * @param  Closure $closure     �հ�
     * @return array
     */
    protected function eagerlyMorphToMany(array $where, string $relation, array $subRelation = [], Closure $closure = null): array
    {
        // Ԥ���������ѯ ֧��Ƕ��Ԥ����
        $this->query->removeOption('where');

        if ($closure) {
            $closure($this);
        }

        $list     = $this->query->where($where)->with($subRelation)->select();
        $morphKey = $this->morphKey;

        // ��װģ������
        $data = [];
        foreach ($list as $set) {
            $key = $set->$morphKey;

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
     * @param  mixed $data ���� ����ʹ������ ����ģ�Ͷ���
     * @param  bool  $replace �Ƿ��Զ�ʶ����º�д��
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
        $pk = $this->parent->getPk();

        $data[$this->morphKey]  = $this->parent->$pk;
        $data[$this->morphType] = $this->type;

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
     * ִ�л�����ѯ����ִ��һ�Σ�
     * @access protected
     * @return void
     */
    protected function baseQuery(): void
    {
        if (empty($this->baseQuery) && $this->parent->getData()) {
            $pk = $this->parent->getPk();

            $this->query->where([
                [$this->morphKey, '=', $this->parent->$pk],
                [$this->morphType, '=', $this->type],
            ]);

            $this->baseQuery = true;
        }
    }

}
