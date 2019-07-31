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

namespace think\model\concern;

use Closure;
use think\App;
use think\Collection;
use think\db\Query;
use think\Exception;
use think\Model;
use think\model\Relation;
use think\model\relation\BelongsTo;
use think\model\relation\BelongsToMany;
use think\model\relation\HasMany;
use think\model\relation\HasManyThrough;
use think\model\relation\HasOne;
use think\model\relation\HasOneThrough;
use think\model\relation\MorphMany;
use think\model\relation\MorphOne;
use think\model\relation\MorphTo;

/**
 * ģ�͹�������
 */
trait RelationShip
{
    /**
     * ������ģ�Ͷ���
     * @var object
     */
    private $parent;

    /**
     * ģ�͹�������
     * @var array
     */
    private $relation = [];

    /**
     * ����д�붨����Ϣ
     * @var array
     */
    private $together = [];

    /**
     * �����Զ�д����Ϣ
     * @var array
     */
    protected $relationWrite = [];

    /**
     * ���ø���������
     * @access public
     * @param  Model $model  ģ�Ͷ���
     * @return $this
     */
    public function setParent(Model $model)
    {
        $this->parent = $model;

        return $this;
    }

    /**
     * ��ȡ����������
     * @access public
     * @return Model
     */
    public function getParent(): Model
    {
        return $this->parent;
    }

    /**
     * ��ȡ��ǰģ�͵Ĺ���ģ������
     * @access public
     * @param  string $name ����������
     * @param  bool   $auto �������Ƿ��Զ���ȡ
     * @return mixed
     */
    public function getRelation(string $name = null, bool $auto = false)
    {
        if (is_null($name)) {
            return $this->relation;
        }

        if (array_key_exists($name, $this->relation)) {
            return $this->relation[$name];
        } elseif ($auto) {
            $relation = App::parseName($name, 1, false);
            return $this->getRelationValue($relation);
        }
    }

    /**
     * ���ù������ݶ���ֵ
     * @access public
     * @param  string $name  ������
     * @param  mixed  $value ����ֵ
     * @param  array  $data  ����
     * @return $this
     */
    public function setRelation(string $name, $value, array $data = [])
    {
        // ����޸���
        $method = 'set' . App::parseName($name, 1) . 'Attr';

        if (method_exists($this, $method)) {
            $value = $this->$method($value, array_merge($this->data, $data));
        }

        $this->relation[$name] = $value;

        return $this;
    }

    /**
     * ��ѯ��ǰģ�͵Ĺ�������
     * @access public
     * @param  array $relations ������
     * @param  array $withRelationAttr   ������ȡ��
     * @return void
     */
    public function relationQuery(array $relations, array $withRelationAttr = []): void
    {
        foreach ($relations as $key => $relation) {
            $subRelation = '';
            $closure     = null;

            if ($relation instanceof Closure) {
                // ֧�ֱհ���ѯ���˹�������
                $closure  = $relation;
                $relation = $key;
            }

            if (is_array($relation)) {
                $subRelation = $relation;
                $relation    = $key;
            } elseif (strpos($relation, '.')) {
                list($relation, $subRelation) = explode('.', $relation, 2);
            }

            $method       = App::parseName($relation, 1, false);
            $relationName = App::parseName($relation);

            $relationResult = $this->$method();

            if (isset($withRelationAttr[$relationName])) {
                $relationResult->getQuery()->withAttr($withRelationAttr[$relationName]);
            }

            $this->relation[$relation] = $relationResult->getRelation($subRelation, $closure);
        }
    }

    /**
     * ��������д��
     * @access public
     * @param  array $relation ����
     * @return $this
     */
    public function together(array $relation)
    {
        $this->together = $relation;

        $this->checkAutoRelationWrite();

        return $this;
    }

    /**
     * ���ݹ���������ѯ��ǰģ��
     * @access public
     * @param  string  $relation ����������
     * @param  mixed   $operator �Ƚϲ�����
     * @param  integer $count    ����
     * @param  string  $id       �������ͳ���ֶ�
     * @param  string  $joinType JOIN����
     * @return Query
     */
    public static function has(string $relation, string $operator = '>=', int $count = 1, string $id = '*', string $joinType = ''): Query
    {
        return (new static())
            ->$relation()
            ->has($operator, $count, $id, $joinType);
    }

    /**
     * ���ݹ���������ѯ��ǰģ��
     * @access public
     * @param  string $relation ����������
     * @param  mixed  $where    ��ѯ������������߱հ���
     * @param  mixed  $fields   �ֶ�
     * @param  string $joinType JOIN����
     * @return Query
     */
    public static function hasWhere(string $relation, $where = [], string $fields = '*', string $joinType = ''): Query
    {
        return (new static())
            ->$relation()
            ->hasWhere($where, $fields, $joinType);
    }

    /**
     * Ԥ���������ѯ �������ݼ�
     * @access public
     * @param  array  $resultSet ���ݼ�
     * @param  string $relation  ������
     * @param  array  $withRelationAttr ������ȡ��
     * @param  bool   $join      �Ƿ�ΪJOIN��ʽ
     * @return void
     */
    public function eagerlyResultSet(array &$resultSet, array $relations, array $withRelationAttr = [], bool $join = false): void
    {
        foreach ($relations as $key => $relation) {
            $subRelation = [];
            $closure     = null;

            if ($relation instanceof Closure) {
                $closure  = $relation;
                $relation = $key;
            }

            if (is_array($relation)) {
                $subRelation = $relation;
                $relation    = $key;
            } elseif (strpos($relation, '.')) {
                list($relation, $subRelation) = explode('.', $relation, 2);

                $subRelation = [$subRelation];
            }

            $relation     = App::parseName($relation, 1, false);
            $relationName = App::parseName($relation);

            $relationResult = $this->$relation();

            if (isset($withRelationAttr[$relationName])) {
                $relationResult->getQuery()->withAttr($withRelationAttr[$relationName]);
            }

            $relationResult->eagerlyResultSet($resultSet, $relation, $subRelation, $closure, $join);
        }
    }

    /**
     * Ԥ���������ѯ ����ģ�Ͷ���
     * @access public
     * @param  Model    $result    ���ݶ���
     * @param  array    $relations ����
     * @param  array    $withRelationAttr ������ȡ��
     * @param  bool     $join      �Ƿ�ΪJOIN��ʽ
     * @return void
     */
    public function eagerlyResult(Model $result, array $relations, array $withRelationAttr = [], bool $join = false): void
    {
        foreach ($relations as $key => $relation) {
            $subRelation = [];
            $closure     = null;

            if ($relation instanceof Closure) {
                $closure  = $relation;
                $relation = $key;
            }

            if (is_array($relation)) {
                $subRelation = $relation;
                $relation    = $key;
            } elseif (strpos($relation, '.')) {
                list($relation, $subRelation) = explode('.', $relation, 2);

                $subRelation = [$subRelation];
            }

            $relation     = App::parseName($relation, 1, false);
            $relationName = App::parseName($relation);

            $relationResult = $this->$relation();

            if (isset($withRelationAttr[$relationName])) {
                $relationResult->getQuery()->withAttr($withRelationAttr[$relationName]);
            }

            $relationResult->eagerlyResult($result, $relation, $subRelation, $closure, $join);
        }
    }

    /**
     * �󶨣�һ��һ���������Ե���ǰģ��
     * @access protected
     * @param  string   $relation    ��������
     * @param  array    $attrs       ������
     * @return $this
     * @throws Exception
     */
    public function bindAttr(string $relation, array $attrs = [])
    {
        $relation = $this->getRelation($relation);

        foreach ($attrs as $key => $attr) {
            $key   = is_numeric($key) ? $attr : $key;
            $value = $this->getOrigin($key);

            if (!is_null($value)) {
                throw new Exception('bind attr has exists:' . $key);
            }

            $this->set($key, $relation ? $relation->$attr : null);
        }

        return $this;
    }

    /**
     * ����ͳ��
     * @access public
     * @param  Model    $result     ���ݶ���
     * @param  array    $relations  ������
     * @param  string   $aggregate  �ۺϲ�ѯ����
     * @param  string   $field      �ֶ�
     * @return void
     */
    public function relationCount(Model $result, array $relations, string $aggregate = 'sum', string $field = '*'): void
    {
        foreach ($relations as $key => $relation) {
            $closure = $name = null;

            if ($relation instanceof Closure) {
                $closure  = $relation;
                $relation = $key;
            } elseif (is_string($key)) {
                $name     = $relation;
                $relation = $key;
            }

            $relation = App::parseName($relation, 1, false);
            $count    = $this->$relation()->relationCount($result, $closure, $aggregate, $field, $name);

            if (empty($name)) {
                $name = App::parseName($relation) . '_' . $aggregate;
            }

            $result->setAttr($name, $count);
        }
    }

    /**
     * HAS ONE ��������
     * @access public
     * @param  string $model      ģ����
     * @param  string $foreignKey �������
     * @param  string $localKey   ��ǰ����
     * @return HasOne
     */
    public function hasOne(string $model, string $foreignKey = '', string $localKey = ''): HasOne
    {
        // ��¼��ǰ������Ϣ
        $model      = $this->parseModel($model);
        $localKey   = $localKey ?: $this->getPk();
        $foreignKey = $foreignKey ?: $this->getForeignKey($this->name);

        return new HasOne($this, $model, $foreignKey, $localKey);
    }

    /**
     * BELONGS TO ��������
     * @access public
     * @param  string $model      ģ����
     * @param  string $foreignKey �������
     * @param  string $localKey   ��������
     * @return BelongsTo
     */
    public function belongsTo(string $model, string $foreignKey = '', string $localKey = ''): BelongsTo
    {
        // ��¼��ǰ������Ϣ
        $model      = $this->parseModel($model);
        $foreignKey = $foreignKey ?: $this->getForeignKey((new $model)->getName());
        $localKey   = $localKey ?: (new $model)->getPk();
        $trace      = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $relation   = App::parseName($trace[1]['function']);

        return new BelongsTo($this, $model, $foreignKey, $localKey, $relation);
    }

    /**
     * HAS MANY ��������
     * @access public
     * @param  string $model      ģ����
     * @param  string $foreignKey �������
     * @param  string $localKey   ��ǰ����
     * @return HasMany
     */
    public function hasMany(string $model, string $foreignKey = '', string $localKey = ''): HasMany
    {
        // ��¼��ǰ������Ϣ
        $model      = $this->parseModel($model);
        $localKey   = $localKey ?: $this->getPk();
        $foreignKey = $foreignKey ?: $this->getForeignKey($this->name);

        return new HasMany($this, $model, $foreignKey, $localKey);
    }

    /**
     * HAS MANY Զ�̹�������
     * @access public
     * @param  string $model      ģ����
     * @param  string $through    �м�ģ����
     * @param  string $foreignKey �������
     * @param  string $throughKey �������
     * @param  string $localKey   ��ǰ����
     * @param  string $throughPk  �м������
     * @return HasManyThrough
     */
    public function hasManyThrough(string $model, string $through, string $foreignKey = '', string $throughKey = '', string $localKey = '', string $throughPk = ''): HasManyThrough
    {
        // ��¼��ǰ������Ϣ
        $model      = $this->parseModel($model);
        $through    = $this->parseModel($through);
        $localKey   = $localKey ?: $this->getPk();
        $foreignKey = $foreignKey ?: $this->getForeignKey($this->name);
        $throughKey = $throughKey ?: $this->getForeignKey((new $through)->getName());
        $throughPk  = $throughPk ?: (new $through)->getPk();

        return new HasManyThrough($this, $model, $through, $foreignKey, $throughKey, $localKey, $throughPk);
    }

    /**
     * HAS ONE Զ�̹�������
     * @access public
     * @param  string $model      ģ����
     * @param  string $through    �м�ģ����
     * @param  string $foreignKey �������
     * @param  string $throughKey �������
     * @param  string $localKey   ��ǰ����
     * @param  string $throughPk  �м������
     * @return HasOneThrough
     */
    public function hasOneThrough(string $model, string $through, string $foreignKey = '', string $throughKey = '', string $localKey = '', string $throughPk = ''): HasOneThrough
    {
        // ��¼��ǰ������Ϣ
        $model      = $this->parseModel($model);
        $through    = $this->parseModel($through);
        $localKey   = $localKey ?: $this->getPk();
        $foreignKey = $foreignKey ?: $this->getForeignKey($this->name);
        $throughKey = $throughKey ?: $this->getForeignKey((new $through)->getName());
        $throughPk  = $throughPk ?: (new $through)->getPk();

        return new HasOneThrough($this, $model, $through, $foreignKey, $throughKey, $localKey, $throughPk);
    }

    /**
     * BELONGS TO MANY ��������
     * @access public
     * @param  string $model      ģ����
     * @param  string $table      �м����
     * @param  string $foreignKey �������
     * @param  string $localKey   ��ǰģ�͹�����
     * @return BelongsToMany
     */
    public function belongsToMany(string $model, string $table = '', string $foreignKey = '', string $localKey = ''): BelongsToMany
    {
        // ��¼��ǰ������Ϣ
        $model      = $this->parseModel($model);
        $name       = App::parseName(App::classBaseName($model));
        $table      = $table ?: App::parseName($this->name) . '_' . $name;
        $foreignKey = $foreignKey ?: $name . '_id';
        $localKey   = $localKey ?: $this->getForeignKey($this->name);

        return new BelongsToMany($this, $model, $table, $foreignKey, $localKey);
    }

    /**
     * MORPH  One ��������
     * @access public
     * @param  string       $model ģ����
     * @param  string|array $morph ��̬�ֶ���Ϣ
     * @param  string       $type  ��̬����
     * @return MorphOne
     */
    public function morphOne(string $model, $morph = null, string $type = ''): MorphOne
    {
        // ��¼��ǰ������Ϣ
        $model = $this->parseModel($model);

        if (is_null($morph)) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $morph = App::parseName($trace[1]['function']);
        }

        if (is_array($morph)) {
            list($morphType, $foreignKey) = $morph;
        } else {
            $morphType  = $morph . '_type';
            $foreignKey = $morph . '_id';
        }

        $type = $type ?: get_class($this);

        return new MorphOne($this, $model, $foreignKey, $morphType, $type);
    }

    /**
     * MORPH  MANY ��������
     * @access public
     * @param  string       $model ģ����
     * @param  string|array $morph ��̬�ֶ���Ϣ
     * @param  string       $type  ��̬����
     * @return MorphMany
     */
    public function morphMany(string $model, $morph = null, string $type = ''): MorphMany
    {
        // ��¼��ǰ������Ϣ
        $model = $this->parseModel($model);

        if (is_null($morph)) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $morph = App::parseName($trace[1]['function']);
        }

        $type = $type ?: get_class($this);

        if (is_array($morph)) {
            list($morphType, $foreignKey) = $morph;
        } else {
            $morphType  = $morph . '_type';
            $foreignKey = $morph . '_id';
        }

        return new MorphMany($this, $model, $foreignKey, $morphType, $type);
    }

    /**
     * MORPH TO ��������
     * @access public
     * @param  string|array $morph ��̬�ֶ���Ϣ
     * @param  array        $alias ��̬��������
     * @return MorphTo
     */
    public function morphTo($morph = null, array $alias = []): MorphTo
    {
        $trace    = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $relation = App::parseName($trace[1]['function']);

        if (is_null($morph)) {
            $morph = $relation;
        }

        // ��¼��ǰ������Ϣ
        if (is_array($morph)) {
            list($morphType, $foreignKey) = $morph;
        } else {
            $morphType  = $morph . '_type';
            $foreignKey = $morph . '_id';
        }

        return new MorphTo($this, $morphType, $foreignKey, $alias, $relation);
    }

    /**
     * ����ģ�͵����������ռ�
     * @access protected
     * @param  string $model ģ��������������������
     * @return string
     */
    protected function parseModel(string $model): string
    {
        if (false === strpos($model, '\\')) {
            $path = explode('\\', static::class);
            array_pop($path);
            array_push($path, App::parseName($model, 1));
            $model = implode('\\', $path);
        }

        return $model;
    }

    /**
     * ��ȡģ�͵�Ĭ�������
     * @access protected
     * @param  string $name ģ����
     * @return string
     */
    protected function getForeignKey(string $name): string
    {
        if (strpos($name, '\\')) {
            $name = App::classBaseName($name);
        }

        return App::parseName($name) . '_id';
    }

    /**
     * ��������Ƿ�Ϊ�������� ������򷵻ع���������
     * @access protected
     * @param  string $attr ����������
     * @return string|false
     */
    protected function isRelationAttr(string $attr)
    {
        $relation = App::parseName($attr, 1, false);

        if (method_exists($this, $relation) && !method_exists('think\Model', $relation)) {
            return $relation;
        }

        return false;
    }

    /**
     * ���ܻ�ȡ����ģ������
     * @access protected
     * @param  Relation $modelRelation ģ�͹�������
     * @return mixed
     */
    protected function getRelationData(Relation $modelRelation)
    {
        if ($this->parent && !$modelRelation->isSelfRelation()
            && get_class($this->parent) == get_class($modelRelation->getModel(false))) {
            return $this->parent;
        }

        // ��ȡ��������
        return $modelRelation->getRelation();
    }

    /**
     * ���������Զ�д����
     * @access protected
     * @return void
     */
    protected function checkAutoRelationWrite(): void
    {
        foreach ($this->together as $key => $name) {
            if (is_array($name)) {
                if (key($name) === 0) {
                    $this->relationWrite[$key] = [];
                    // �󶨹�������
                    foreach ($name as $val) {
                        if (isset($this->data[$val])) {
                            $this->relationWrite[$key][$val] = $this->data[$val];
                        }
                    }
                } else {
                    // ֱ�Ӵ����������
                    $this->relationWrite[$key] = $name;
                }
            } elseif (isset($this->relation[$name])) {
                $this->relationWrite[$name] = $this->relation[$name];
            } elseif (isset($this->data[$name])) {
                $this->relationWrite[$name] = $this->data[$name];
                unset($this->data[$name]);
            }
        }
    }

    /**
     * �Զ��������ݸ��£����һ��һ������
     * @access protected
     * @return void
     */
    protected function autoRelationUpdate(): void
    {
        foreach ($this->relationWrite as $name => $val) {
            if ($val instanceof Model) {
                $val->exists(true)->save();
            } else {
                $model = $this->getRelation($name, true);

                if ($model instanceof Model) {
                    $model->exists(true)->save($val);
                }
            }
        }
    }

    /**
     * �Զ���������д�루���һ��һ������
     * @access protected
     * @return void
     */
    protected function autoRelationInsert(): void
    {
        foreach ($this->relationWrite as $name => $val) {
            $method = App::parseName($name, 1, false);
            $this->$method()->save($val);
        }
    }

    /**
     * �Զ���������ɾ����֧��һ��һ��һ�Զ������
     * @access protected
     * @return void
     */
    protected function autoRelationDelete(): void
    {
        foreach ($this->relationWrite as $key => $name) {
            $name   = is_numeric($key) ? $name : $key;
            $result = $this->getRelation($name, true);

            if ($result instanceof Model) {
                $result->delete();
            } elseif ($result instanceof Collection) {
                foreach ($result as $model) {
                    $model->delete();
                }
            }
        }
    }

    /**
     * �Ƴ���ǰģ�͵Ĺ�������
     * @access public
     * @return $this
     */
    public function removeRelation()
    {
        $this->relation = [];
        return $this;
    }
}
