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
use think\Exception;
use think\Model;
use think\model\Relation;

/**
 * ��̬������
 */
class MorphTo extends Relation
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
     * @var array
     */
    protected $alias = [];

    /**
     * ������
     * @var string
     */
    protected $relation;

    /**
     * �ܹ�����
     * @access public
     * @param  Model  $parent    �ϼ�ģ�Ͷ���
     * @param  string $morphType ��̬�ֶ���
     * @param  string $morphKey  �����
     * @param  array  $alias     ��̬��������
     * @param  string $relation  ������
     */
    public function __construct(Model $parent, string $morphType, string $morphKey, array $alias = [], string $relation = null)
    {
        $this->parent    = $parent;
        $this->morphType = $morphType;
        $this->morphKey  = $morphKey;
        $this->alias     = $alias;
        $this->relation  = $relation;
    }

    /**
     * ��ȡ��ǰ�Ĺ���ģ�����ʵ��
     * @access public
     * @return Model
     */
    public function getModel(): Model
    {
        $morphType = $this->morphType;
        $model     = $this->parseModel($this->parent->$morphType);

        return (new $model);
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
        $morphKey  = $this->morphKey;
        $morphType = $this->morphType;

        // ��̬ģ��
        $model = $this->parseModel($this->parent->$morphType);

        // ��������
        $pk = $this->parent->$morphKey;

        $relationModel = (new $model)->relation($subRelation)->find($pk);

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
     * ����ģ�͵����������ռ�
     * @access protected
     * @param  string $model ģ��������������������
     * @return string
     */
    protected function parseModel(string $model): string
    {
        if (isset($this->alias[$model])) {
            $model = $this->alias[$model];
        }

        if (false === strpos($model, '\\')) {
            $path = explode('\\', get_class($this->parent));
            array_pop($path);
            array_push($path, App::parseName($model, 1));
            $model = implode('\\', $path);
        }

        return $model;
    }

    /**
     * ���ö�̬����
     * @access public
     * @param  array $alias ��������
     * @return $this
     */
    public function setAlias(array $alias)
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * �Ƴ�������ѯ����
     * @access public
     * @return $this
     */
    public function removeOption()
    {
        return $this;
    }

    /**
     * Ԥ���������ѯ
     * @access public
     * @param  array   $resultSet   ���ݼ�
     * @param  string  $relation    ��ǰ������
     * @param  array   $subRelation �ӹ�����
     * @param  Closure $closure     �հ�
     * @return void
     * @throws Exception
     */
    public function eagerlyResultSet(array &$resultSet, string $relation, array $subRelation, Closure $closure = null): void
    {
        $morphKey  = $this->morphKey;
        $morphType = $this->morphType;
        $range     = [];

        foreach ($resultSet as $result) {
            // ��ȡ��������б�
            if (!empty($result->$morphKey)) {
                $range[$result->$morphType][] = $result->$morphKey;
            }
        }

        if (!empty($range)) {
            // ����������
            $attr = App::parseName($relation);

            foreach ($range as $key => $val) {
                // ��̬����ӳ��
                $model = $this->parseModel($key);
                $obj   = new $model;
                $pk    = $obj->getPk();
                $list  = $obj->all($val, $subRelation);
                $data  = [];

                foreach ($list as $k => $vo) {
                    $data[$vo->$pk] = $vo;
                }

                foreach ($resultSet as $result) {
                    if ($key == $result->$morphType) {
                        // ����ģ��
                        if (!isset($data[$result->$morphKey])) {
                            $relationModel = null;
                            throw new Exception('relation data not exists :' . $this->model);
                        } else {
                            $relationModel = $data[$result->$morphKey];
                            $relationModel->setParent(clone $result);
                            $relationModel->exists(true);
                        }

                        $result->setRelation($attr, $relationModel);
                    }
                }
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
        // ��̬����ӳ��
        $model = $this->parseModel($result->{$this->morphType});

        $this->eagerlyMorphToOne($model, $relation, $result, $subRelation);
    }

    /**
     * ����ͳ��
     * @access public
     * @param  Model   $result  ���ݶ���
     * @param  Closure $closure �հ�
     * @param  string  $aggregate �ۺϲ�ѯ����
     * @param  string  $field �ֶ�
     * @return integer
     */
    public function relationCount(Model $result, Closure $closure = null, string $aggregate = 'count', string $field = '*')
    {}

    /**
     * ��̬MorphTo ����ģ��Ԥ��ѯ
     * @access protected
     * @param  string $model       ����ģ�Ͷ���
     * @param  string $relation    ������
     * @param  Model  $result
     * @param  array  $subRelation �ӹ���
     * @return void
     */
    protected function eagerlyMorphToOne(string $model, string $relation, Model $result, array $subRelation = []): void
    {
        // Ԥ���������ѯ ֧��Ƕ��Ԥ����
        $pk   = $this->parent->{$this->morphKey};
        $data = (new $model)->with($subRelation)->find($pk);

        if ($data) {
            $data->setParent(clone $result);
            $data->exists(true);
        }

        $result->setRelation(App::parseName($relation), $data ?: null);
    }

    /**
     * ��ӹ�������
     * @access public
     * @param  Model  $model  ����ģ�Ͷ���
     * @param  string $type   ��̬����
     * @return Model
     */
    public function associate(Model $model, string $type = ''): Model
    {
        $morphKey  = $this->morphKey;
        $morphType = $this->morphType;
        $pk        = $model->getPk();

        $this->parent->setAttr($morphKey, $model->$pk);
        $this->parent->setAttr($morphType, $type ?: get_class($model));
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
        $morphKey  = $this->morphKey;
        $morphType = $this->morphType;

        $this->parent->setAttr($morphKey, null);
        $this->parent->setAttr($morphType, null);
        $this->parent->save();

        return $this->parent->setRelation($this->relation, null);
    }

}
