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
 * һ��һ����������
 * @package think\model\relation
 */
abstract class OneToOne extends Relation
{
    /**
     * JOIN����
     * @var string
     */
    protected $joinType = 'INNER';

    /**
     * �󶨵Ĺ�������
     * @var array
     */
    protected $bindAttr = [];

    /**
     * ������
     * @var string
     */
    protected $relation;

    /**
     * ����join����
     * @access public
     * @param  string $type JOIN����
     * @return $this
     */
    public function joinType(string $type)
    {
        $this->joinType = $type;
        return $this;
    }

    /**
     * Ԥ���������ѯ��JOIN��ʽ��
     * @access public
     * @param  Query   $query       ��ѯ����
     * @param  string  $relation    ������
     * @param  mixed   $field       �����ֶ�
     * @param  string  $joinType    JOIN��ʽ
     * @param  Closure $closure     �հ�����
     * @param  bool    $first
     * @return void
     */
    public function eagerly(Query $query, string $relation, $field = true, string $joinType = '', Closure $closure = null, bool $first = false): void
    {
        $name = App::parseName(App::classBaseName($this->parent));

        if ($first) {
            $table = $query->getTable();
            $query->table([$table => $name]);

            if ($query->getOptions('field')) {
                $masterField = $query->getOptions('field');
                $query->removeOption('field');
            } else {
                $masterField = true;
            }

            $query->tableField($masterField, $table, $name);
        }

        // Ԥ�����װ
        $joinTable = $this->query->getTable();
        $joinAlias = $relation;
        $joinType  = $joinType ?: $this->joinType;

        $query->via($joinAlias);

        if ($this instanceof BelongsTo) {
            $joinOn = $name . '.' . $this->foreignKey . '=' . $joinAlias . '.' . $this->localKey;
        } else {
            $joinOn = $name . '.' . $this->localKey . '=' . $joinAlias . '.' . $this->foreignKey;
        }

        if ($closure) {
            // ִ�бհ���ѯ
            $closure($query);
            // ʹ��withFieldָ����ȡ�������ֶ�
            if ($this->withField) {
                $field = $this->withField;
            }
        }

        $query->join([$joinTable => $joinAlias], $joinOn, $joinType)
            ->tableField($field, $joinTable, $joinAlias, $relation . '__');
    }

    /**
     *  Ԥ���������ѯ�����ݼ���
     * @access protected
     * @param  array   $resultSet
     * @param  string  $relation
     * @param  array   $subRelation
     * @param  Closure $closure
     * @return mixed
     */
    abstract protected function eagerlySet(array &$resultSet, string $relation, array $subRelation = [], Closure $closure = null);

    /**
     * Ԥ���������ѯ�����ݣ�
     * @access protected
     * @param  Model   $result
     * @param  string  $relation
     * @param  array   $subRelation
     * @param  Closure $closure
     * @return mixed
     */
    abstract protected function eagerlyOne(Model $result, string $relation, array $subRelation = [], Closure $closure = null);

    /**
     * Ԥ���������ѯ�����ݼ���
     * @access public
     * @param  array   $resultSet   ���ݼ�
     * @param  string  $relation    ��ǰ������
     * @param  array   $subRelation �ӹ�����
     * @param  Closure $closure     �հ�
     * @param  bool    $join        �Ƿ�ΪJOIN��ʽ
     * @return void
     */
    public function eagerlyResultSet(array &$resultSet, string $relation, array $subRelation = [], Closure $closure = null, bool $join = false): void
    {
        if ($join) {
            // ģ��JOIN������װ
            foreach ($resultSet as $result) {
                $this->match($this->model, $relation, $result);
            }
        } else {
            // IN��ѯ
            $this->eagerlySet($resultSet, $relation, $subRelation, $closure);
        }
    }

    /**
     * Ԥ���������ѯ�����ݣ�
     * @access public
     * @param  Model   $result      ���ݶ���
     * @param  string  $relation    ��ǰ������
     * @param  array   $subRelation �ӹ�����
     * @param  Closure $closure     �հ�
     * @param  bool    $join        �Ƿ�ΪJOIN��ʽ
     * @return void
     */
    public function eagerlyResult(Model $result, string $relation, array $subRelation = [], Closure $closure = null, bool $join = false): void
    {
        if ($join) {
            // ģ��JOIN������װ
            $this->match($this->model, $relation, $result);
        } else {
            // IN��ѯ
            $this->eagerlyOne($result, $relation, $subRelation, $closure);
        }
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
        if ($data instanceof Model) {
            $data = $data->getData();
        }

        $model = new $this->model;
        // �������������
        $data[$this->foreignKey] = $this->parent->{$this->localKey};

        return $model->replace($replace)->save($data) ? $model : false;
    }

    /**
     * �󶨹���������Ե���ģ������
     * @access public
     * @param  array $attr Ҫ�󶨵������б�
     * @return $this
     */
    public function bind(array $attr)
    {
        $this->bindAttr = $attr;

        return $this;
    }

    /**
     * ��ȡ������
     * @access public
     * @return array
     */
    public function getBindAttr(): array
    {
        return $this->bindAttr;
    }

    /**
     * һ��һ ����ģ��Ԥ��ѯƴװ
     * @access public
     * @param  string $model    ģ������
     * @param  string $relation ������
     * @param  Model  $result   ģ�Ͷ���ʵ��
     * @return void
     */
    protected function match(string $model, string $relation, Model $result): void
    {
        // ������װģ������
        foreach ($result->getData() as $key => $val) {
            if (strpos($key, '__')) {
                list($name, $attr) = explode('__', $key, 2);
                if ($name == $relation) {
                    $list[$name][$attr] = $val;
                    unset($result->$key);
                }
            }
        }

        if (isset($list[$relation])) {
            $array = array_unique($list[$relation]);

            if (count($array) == 1 && null === current($array)) {
                $relationModel = null;
            } else {
                $relationModel = new $model($list[$relation]);
                $relationModel->setParent(clone $result);
                $relationModel->exists(true);
            }

            if ($relationModel && !empty($this->bindAttr)) {
                $this->bindAttr($relationModel, $result);
            }
        } else {
            $relationModel = null;
        }

        $result->setRelation(App::parseName($relation), $relationModel);
    }

    /**
     * �󶨹������Ե���ģ��
     * @access protected
     * @param  Model $model  ����ģ�Ͷ���
     * @param  Model $result ��ģ�Ͷ���
     * @return void
     * @throws Exception
     */
    protected function bindAttr(Model $model, Model $result): void
    {
        foreach ($this->bindAttr as $key => $attr) {
            $key   = is_numeric($key) ? $attr : $key;
            $value = $result->getOrigin($key);

            if (!is_null($value)) {
                throw new Exception('bind attr has exists:' . $key);
            }

            $result->setAttr($key, $model ? $model->$attr : null);
        }
    }

    /**
     * һ��һ ����ģ��Ԥ��ѯ��IN��ʽ��
     * @access public
     * @param  array   $where       ����Ԥ��ѯ����
     * @param  string  $key         ��������
     * @param  string  $relation    ������
     * @param  array   $subRelation �ӹ���
     * @param  Closure $closure
     * @return array
     */
    protected function eagerlyWhere(array $where, string $key, string $relation, array $subRelation = [], Closure $closure = null)
    {
        // Ԥ���������ѯ ֧��Ƕ��Ԥ����
        if ($closure) {
            $closure($this);
        }

        if ($this->withField) {
            $this->query->field($this->withField);
        }

        if ($this->query->getOptions('order')) {
            $this->query->group($key);
        }

        $list = $this->query->where($where)->with($subRelation)->select();

        // ��װģ������
        $data = [];

        foreach ($list as $set) {
            if (!isset($data[$set->$key])) {
                $data[$set->$key] = $set;
            }
        }

        return $data;
    }

}
