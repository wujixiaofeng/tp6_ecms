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
use think\db\Query;
use think\Exception;
use think\Model;
use think\model\Relation;

/**
 * ��̬һ��һ������
 */
class MorphOne extends Relation
{
    /**
     * ��̬�������
     * @var string
     */
    protected $morphKey;

    /**
     * ��̬�ֶ�
     * @var string
     */
    protected $morphType;

    /**
     * ��̬����
     * @var string
     */
    protected $type;

    /**
     * ���캯��
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
     * @return Model
     */
    public function getRelation(array $subRelation = [], Closure $closure = null)
    {
        if ($closure) {
            $closure($this);
        }

        $this->baseQuery();

        $relationModel = $this->query->relation($subRelation)->find();

        if ($relationModel) {
            $relationModel->setParent(clone $this->parent);
        }

        return $relationModel;
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
        return $this->parent;
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
            $data = $this->eagerlyMorphToOne([
                [$morphKey, 'in', $range],
                [$morphType, '=', $type],
            ], $relation, $subRelation, $closure);

            // ����������
            $attr = App::parseName($relation);

            // �������ݷ�װ
            foreach ($resultSet as $result) {
                if (!isset($data[$result->$pk])) {
                    $relationModel = null;
                } else {
                    $relationModel = $data[$result->$pk];
                    $relationModel->setParent(clone $result);
                    $relationModel->exists(true);
                }

                $result->setRelation($attr, $relationModel);
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
            $pk   = $result->$pk;
            $data = $this->eagerlyMorphToOne([
                [$this->morphKey, '=', $pk],
                [$this->morphType, '=', $this->type],
            ], $relation, $subRelation, $closure);

            if (isset($data[$pk])) {
                $relationModel = $data[$pk];
                $relationModel->setParent(clone $result);
                $relationModel->exists(true);
            } else {
                $relationModel = null;
            }

            $result->setRelation(App::parseName($relation), $relationModel);
        }
    }

    /**
     * ��̬һ��һ ����ģ��Ԥ��ѯ
     * @access protected
     * @param  array   $where       ����Ԥ��ѯ����
     * @param  string  $relation    ������
     * @param  array   $subRelation �ӹ���
     * @param  Closure $closure     �հ�
     * @return array
     */
    protected function eagerlyMorphToOne(array $where, string $relation, array $subRelation = [], $closure = null): array
    {
        // Ԥ���������ѯ ֧��Ƕ��Ԥ����
        if ($closure) {
            $closure($this);
        }

        $list     = $this->query->where($where)->with($subRelation)->select();
        $morphKey = $this->morphKey;

        // ��װģ������
        $data = [];

        foreach ($list as $set) {
            $data[$set->$morphKey] = $set;
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
        $pk = $this->parent->getPk();

        $data[$this->morphKey]  = $this->parent->$pk;
        $data[$this->morphType] = $this->type;

        return new $this->model($data);
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
