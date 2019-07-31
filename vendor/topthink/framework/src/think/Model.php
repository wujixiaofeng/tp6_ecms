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

namespace think;

use ArrayAccess;
use Closure;
use JsonSerializable;
use think\db\Query;

/**
 * Class Model
 * @package think
 * @mixin Query
 * @method void onAfterRead(Model $model) static after_read�¼�����
 * @method mixed onBeforeInsert(Model $model) static before_insert�¼�����
 * @method void onAfterInsert(Model $model) static after_insert�¼�����
 * @method mixed onBeforeUpdate(Model $model) static before_update�¼�����
 * @method void onAfterUpdate(Model $model) static after_update�¼�����
 * @method mixed onBeforeWrite(Model $model) static before_write�¼�����
 * @method void onAfterWrite(Model $model) static after_write�¼�����
 * @method mixed onBeforeDelete(Model $model) static before_write�¼�����
 * @method void onAfterDelete(Model $model) static after_delete�¼�����
 * @method void onBeforeRestore(Model $model) static before_restore�¼�����
 * @method void onAfterRestore(Model $model) static after_restore�¼�����
 */
abstract class Model implements JsonSerializable, ArrayAccess
{
    use model\concern\Attribute;
    use model\concern\RelationShip;
    use model\concern\ModelEvent;
    use model\concern\TimeStamp;
    use model\concern\Conversion;

    /**
     * �����Ƿ����
     * @var bool
     */
    private $exists = false;

    /**
     * �Ƿ�ǿ�Ƹ�����������
     * @var bool
     */
    private $force = false;

    /**
     * �Ƿ�Replace
     * @var bool
     */
    private $replace = false;

    /**
     * ���ݱ��׺
     * @var string
     */
    protected $suffix;

    /**
     * ��������
     * @var array
     */
    private $updateWhere;

    /**
     * ���ݿ�����
     * @var string
     */
    protected $connection;

    /**
     * ģ������
     * @var string
     */
    protected $name;

    /**
     * ���ݱ�����
     * @var string
     */
    protected $table;

    /**
     * ��ʼ������ģ��.
     * @var array
     */
    protected static $initialized = [];

    /**
     * ��ѯ����ʵ��
     * @var Query
     */
    protected $queryInstance;

    /**
     * ��ɾ���ֶ�Ĭ��ֵ
     * @var mixed
     */
    protected $defaultSoftDelete;

    /**
     * ȫ�ֲ�ѯ��Χ
     * @var array
     */
    protected $globalScope = [];

    /**
     * �ӳٱ�����Ϣ
     * @var bool
     */
    private $lazySave = false;

    /**
     * Db����
     * @var Db
     */
    protected $db;

    /**
     * Event����
     * @var Event
     */
    protected $event;

    /**
     * ����ע��
     * @var Closure
     */
    protected static $maker;

    /**
     * ���÷���ע��
     * @access public
     * @param Closure $maker
     * @return void
     */
    public static function maker(Closure $maker)
    {
        static::$maker = $maker;
    }

    /**
     * ����Db����
     * @access public
     * @param Db $db Db����
     * @return void
     */
    public function setDb(Db $db)
    {
        $this->db = $db;
    }

    /**
     * ����Event����
     * @access public
     * @param Event $event Event����
     * @return void
     */
    public function setEvent(Event $event)
    {
        $this->event = $event;
    }

    /**
     * ����Connection��Ϣ
     * @access public
     * @param mixed $connection ���ݿ�����
     * @return void
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;
    }

    /**
     * ��ȡConnection��Ϣ
     * @access public
     * @return string|array
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * �ܹ�����
     * @access public
     * @param array $data ����
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;

        if (!empty($this->data)) {
            // �����ֶ�
            foreach ((array) $this->disuse as $key) {
                if (array_key_exists($key, $this->data)) {
                    unset($this->data[$key]);
                }
            }
        }

        // ��¼ԭʼ����
        $this->origin = $this->data;

        if (empty($this->name)) {
            // ��ǰģ����
            $name       = str_replace('\\', '/', static::class);
            $this->name = basename($name);
        }

        if (static::$maker) {
            call_user_func(static::$maker, $this);
        }

        // ִ�г�ʼ������
        $this->initialize();
    }

    /**
     * ��ȡ��ǰģ������
     * @access public
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * �����µ�ģ��ʵ��
     * @access public
     * @param array $data  ����
     * @param mixed $where ��������
     * @return Model
     */
    public function newInstance(array $data = [], $where = null): Model
    {
        if (empty($data)) {
            return new static();
        }

        $model = (new static($data))->exists(true);
        $model->setUpdateWhere($where);

        $model->trigger('AfterRead');

        return $model;
    }

    /**
     * ����ģ�͵ĸ�������
     * @access protected
     * @param mixed $where ��������
     * @return void
     */
    protected function setUpdateWhere($where): void
    {
        $this->updateWhere = $where;
    }

    /**
     * ���õ�ǰģ�͵����ݿ��ѯ����
     * @access public
     * @param Query $query ��ѯ����ʵ��
     * @param bool  $clear �Ƿ���Ҫ��ղ�ѯ����
     * @return $this
     */
    public function setQuery(Query $query, bool $clear = true)
    {
        $this->queryInstance = clone $query;

        if ($clear) {
            $this->queryInstance->removeOption();
        }

        return $this;
    }

    /**
     * ��ȡ��ǰģ�͵����ݿ��ѯ����
     * @access public
     * @return Query|null
     */
    public function getQuery()
    {
        return $this->queryInstance;
    }

    /**
     * ���õ�ǰģ�����ݱ�ĺ�׺
     * @access public
     * @param string $suffix ���ݱ��׺
     * @return $this
     */
    public function setSuffix(string $suffix)
    {
        $this->suffix = $suffix;
        return $this;
    }

    /**
     * ��ȡ��ǰģ�͵����ݱ��׺
     * @access public
     * @return string
     */
    public function getSuffix(): string
    {
        return $this->suffix ?: '';
    }

    /**
     * ��ȡ��ǰģ�͵����ݿ��ѯ����
     * @access public
     * @param array $scope ���ò�ʹ�õ�ȫ�ֲ�ѯ��Χ
     * @return Query
     */
    public function db($scope = []): Query
    {
        /** @var Query $query */
        if ($this->queryInstance) {
            $query = $this->queryInstance;
        } else {
            $query = $this->db->buildQuery($this->connection)
                ->name($this->name . $this->suffix)
                ->pk($this->pk);

            if (!empty($this->table)) {
                $query->table($this->table . $this->suffix);
            }
        }

        $query->model($this)
            ->json($this->json, $this->jsonAssoc)
            ->setFieldType(array_merge($this->schema, $this->jsonType));

        // ��ɾ��
        if (property_exists($this, 'withTrashed') && !$this->withTrashed) {
            $this->withNoTrashed($query);
        }

        // ȫ��������
        if (is_array($scope)) {
            $globalScope = array_diff($this->globalScope, $scope);
            $query->scope($globalScope);
        }

        // ���ص�ǰģ�͵����ݿ��ѯ����
        return $query;
    }

    /**
     *  ��ʼ��ģ��
     * @access private
     * @return void
     */
    private function initialize(): void
    {
        if (!isset(static::$initialized[static::class])) {
            static::$initialized[static::class] = true;
            static::init();
        }
    }

    /**
     * ��ʼ������
     * @access protected
     * @return void
     */
    protected static function init()
    {}

    protected function checkData(): void
    {}

    protected function checkResult($result): void
    {}

    /**
     * �����Ƿ�ǿ��д������ �������Ƚ�
     * @access public
     * @param bool $force
     * @return $this
     */
    public function force(bool $force = true)
    {
        $this->force = $force;
        return $this;
    }

    /**
     * �ж�force
     * @access public
     * @return bool
     */
    public function isForce(): bool
    {
        return $this->force;
    }

    /**
     * ���������Ƿ�ʹ��Replace
     * @access public
     * @param bool $replace
     * @return $this
     */
    public function replace(bool $replace = true)
    {
        $this->replace = $replace;
        return $this;
    }

    /**
     * ˢ��ģ������
     * @access public
     * @param bool $relation �Ƿ�ˢ�¹�������
     * @return $this
     */
    public function refresh(bool $relation = false)
    {
        if ($this->exists) {
            $this->data   = $this->db()->find($this->getKey())->getData();
            $this->origin = $this->data;

            if ($relation) {
                $this->relation = [];
            }
        }

        return $this;
    }

    /**
     * ���������Ƿ����
     * @access public
     * @param bool $exists
     * @return $this
     */
    public function exists(bool $exists = true)
    {
        $this->exists = $exists;
        return $this;
    }

    /**
     * �ж������Ƿ�������ݿ�
     * @access public
     * @return bool
     */
    public function isExists(): bool
    {
        return $this->exists;
    }

    /**
     * �ж�ģ���Ƿ�Ϊ��
     * @access public
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->data);
    }

    /**
     * �ӳٱ��浱ǰ���ݶ���
     * @access public
     * @param array|bool $data ����
     * @return void
     */
    public function lazySave($data = []): void
    {
        if (false === $data) {
            $this->lazySave = false;
        } else {
            if (is_array($data)) {
                $this->setAttrs($data);
            }

            $this->lazySave = true;
        }
    }

    /**
     * ���浱ǰ���ݶ���
     * @access public
     * @param array  $data     ����
     * @param string $sequence ����������
     * @return bool
     */
    public function save(array $data = [], string $sequence = null): bool
    {
        // ���ݶ���ֵ
        $this->setAttrs($data);

        if ($this->isEmpty() || false === $this->trigger('BeforeWrite')) {
            return false;
        }

        $result = $this->exists ? $this->updateData() : $this->insertData($sequence);

        if (false === $result) {
            return false;
        }

        // д��ص�
        $this->trigger('AfterWrite');

        // ���¼�¼ԭʼ����
        $this->origin   = $this->data;
        $this->set      = [];
        $this->lazySave = false;

        return true;
    }

    /**
     * ��������Ƿ�����д��
     * @access protected
     * @return array
     */
    protected function checkAllowFields(): array
    {
        // ����ֶ�
        if (empty($this->field)) {
            if (!empty($this->schema)) {
                $this->field = array_keys(array_merge($this->schema, $this->jsonType));
            } else {
                $query = $this->db();
                $table = $this->table ? $this->table . $this->suffix : $query->getTable();

                $this->field = $query->getConnection()->getTableFields($table);
            }

            return $this->field;
        }

        $field = $this->field;

        if ($this->autoWriteTimestamp) {
            array_push($field, $this->createTime, $this->updateTime);
        }

        if (!empty($this->disuse)) {
            // �����ֶ�
            $field = array_diff($field, $this->disuse);
        }

        return $field;
    }

    /**
     * ����д������
     * @access protected
     * @return bool
     */
    protected function updateData(): bool
    {
        // �¼��ص�
        if (false === $this->trigger('BeforeUpdate')) {
            return false;
        }

        $this->checkData();

        // ��ȡ�и��µ�����
        $data = $this->getChangedData();

        if (empty($data)) {
            // ��������
            if (!empty($this->relationWrite)) {
                $this->autoRelationUpdate();
            }

            return true;
        }

        if ($this->autoWriteTimestamp && $this->updateTime && !isset($data[$this->updateTime])) {
            // �Զ�д�����ʱ��
            $data[$this->updateTime]       = $this->autoWriteTimestamp($this->updateTime);
            $this->data[$this->updateTime] = $data[$this->updateTime];
        }

        // ��������ֶ�
        $allowFields = $this->checkAllowFields();

        foreach ($this->relationWrite as $name => $val) {
            if (!is_array($val)) {
                continue;
            }

            foreach ($val as $key) {
                if (isset($data[$key])) {
                    unset($data[$key]);
                }
            }
        }

        // ģ�͸���
        $db = $this->db();
        $db->startTrans();

        try {
            $where  = $this->getWhere();
            $result = $db->where($where)
                ->strict(false)
                ->field($allowFields)
                ->update($data);

            $this->checkResult($result);

            // ��������
            if (!empty($this->relationWrite)) {
                $this->autoRelationUpdate();
            }

            $db->commit();

            // ���»ص�
            $this->trigger('AfterUpdate');

            return true;
        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    /**
     * ����д������
     * @access protected
     * @param string $sequence ������
     * @return bool
     */
    protected function insertData(string $sequence = null): bool
    {
        // ʱ����Զ�д��
        if ($this->autoWriteTimestamp) {
            if ($this->createTime && !isset($this->data[$this->createTime])) {
                $this->data[$this->createTime] = $this->autoWriteTimestamp($this->createTime);
            }

            if ($this->updateTime && !isset($this->data[$this->updateTime])) {
                $this->data[$this->updateTime] = $this->autoWriteTimestamp($this->updateTime);
            }
        }

        if (false === $this->trigger('BeforeInsert')) {
            return false;
        }

        $this->checkData();

        // ��������ֶ�
        $allowFields = $this->checkAllowFields();

        $db = $this->db();
        $db->startTrans();

        try {
            $result = $db->strict(false)
                ->field($allowFields)
                ->replace($this->replace)
                ->insert($this->data, false, $sequence);

            // ��ȡ�Զ���������
            if ($result && $insertId = $db->getLastInsID($sequence)) {
                $pk = $this->getPk();

                if (is_string($pk) && (!isset($this->data[$pk]) || '' == $this->data[$pk])) {
                    $this->data[$pk] = $insertId;
                }
            }

            // ����д��
            if (!empty($this->relationWrite)) {
                $this->autoRelationInsert();
            }

            $db->commit();

            // ��������Ѿ�����
            $this->exists = true;

            // �����ص�
            $this->trigger('AfterInsert');

            return true;
        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    /**
     * ��ȡ��ǰ�ĸ�������
     * @access public
     * @return mixed
     */
    public function getWhere()
    {
        $pk = $this->getPk();

        if (is_string($pk) && isset($this->data[$pk])) {
            $where = [[$pk, '=', $this->data[$pk]]];
        } elseif (!empty($this->updateWhere)) {
            $where = $this->updateWhere;
        } else {
            $where = null;
        }

        return $where;
    }

    /**
     * ���������ݵ���ǰ���ݶ���
     * @access public
     * @param iterable $dataSet ����
     * @param boolean  $replace �Ƿ��Զ�ʶ����º�д��
     * @return Collection
     * @throws \Exception
     */
    public function saveAll(iterable $dataSet, bool $replace = true): Collection
    {
        $db = $this->db();
        $db->startTrans();

        try {
            $pk = $this->getPk();

            if (is_string($pk) && $replace) {
                $auto = true;
            }

            $result = [];

            foreach ($dataSet as $key => $data) {
                if ($this->exists || (!empty($auto) && isset($data[$pk]))) {
                    $result[$key] = self::update($data);
                } else {
                    $result[$key] = self::create($data, $this->field, $this->replace);
                }
            }

            $db->commit();

            return $this->toCollection($result);
        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    /**
     * ɾ����ǰ�ļ�¼
     * @access public
     * @return bool
     */
    public function delete(): bool
    {
        if (!$this->exists || $this->isEmpty() || false === $this->trigger('BeforeDelete')) {
            return false;
        }

        // ��ȡ��������
        $where = $this->getWhere();

        $db = $this->db();
        $db->startTrans();

        try {
            // ɾ����ǰģ������
            $db->where($where)->delete();

            // ����ɾ��
            if (!empty($this->relationWrite)) {
                $this->autoRelationDelete();
            }

            $db->commit();

            $this->trigger('AfterDelete');

            $this->exists   = false;
            $this->lazySave = false;

            return true;
        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    /**
     * д������
     * @access public
     * @param array $data       ��������
     * @param array $allowField �����ֶ�
     * @param bool  $replace    ʹ��Replace
     * @return static
     */
    public static function create(array $data, array $allowField = [], bool $replace = false): Model
    {
        $model = new static();

        if (!empty($allowField)) {
            $model->allowField($allowField);
        }

        $model->replace($replace)->save($data);

        return $model;
    }

    /**
     * ��������
     * @access public
     * @param array $data       ��������
     * @param mixed $where      ��������
     * @param array $allowField �����ֶ�
     * @return static
     */
    public static function update(array $data, $where = [], array $allowField = [])
    {
        $model = new static();

        if (!empty($allowField)) {
            $model->allowField($allowField);
        }

        if (!empty($where)) {
            $model->setUpdateWhere($where);
        }

        $model->exists(true)->save($data);

        return $model;
    }

    /**
     * ɾ����¼
     * @access public
     * @param mixed $data  �����б� ֧�ֱհ���ѯ����
     * @param bool  $force �Ƿ�ǿ��ɾ��
     * @return bool
     */
    public static function destroy($data, bool $force = false): bool
    {
        if (empty($data) && 0 !== $data) {
            return false;
        }

        $model = new static();

        $query = $model->db();

        if (is_array($data) && key($data) !== 0) {
            $query->where($data);
            $data = null;
        } elseif ($data instanceof \Closure) {
            $data($query);
            $data = null;
        }

        $resultSet = $query->select($data);

        foreach ($resultSet as $result) {
            $result->force($force)->delete();
        }

        return true;
    }

    /**
     * �����л�����
     */
    public function __wakeup()
    {
        $this->initialize();
    }

    public function __debugInfo()
    {
        $attrs = get_object_vars($this);

        foreach (['db', 'queryInstance', 'event'] as $name) {
            unset($attrs[$name]);
        }

        return $attrs;
    }

    /**
     * �޸��� �������ݶ����ֵ
     * @access public
     * @param string $name  ����
     * @param mixed  $value ֵ
     * @return void
     */
    public function __set(string $name, $value): void
    {
        $this->setAttr($name, $value);
    }

    /**
     * ��ȡ�� ��ȡ���ݶ����ֵ
     * @access public
     * @param string $name ����
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->getAttr($name);
    }

    /**
     * ������ݶ����ֵ
     * @access public
     * @param string $name ����
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return !is_null($this->getAttr($name));
    }

    /**
     * �������ݶ����ֵ
     * @access public
     * @param string $name ����
     * @return void
     */
    public function __unset(string $name): void
    {
        unset($this->data[$name], $this->relation[$name]);
    }

    // ArrayAccess
    public function offsetSet($name, $value)
    {
        $this->setAttr($name, $value);
    }

    public function offsetExists($name): bool
    {
        return $this->__isset($name);
    }

    public function offsetUnset($name)
    {
        $this->__unset($name);
    }

    public function offsetGet($name)
    {
        return $this->getAttr($name);
    }

    /**
     * ���ò�ʹ�õ�ȫ�ֲ�ѯ��Χ
     * @access public
     * @param array $scope �����õ�ȫ�ֲ�ѯ��Χ
     * @return Query
     */
    public static function withoutGlobalScope(array $scope = null)
    {
        $model = new static();

        return $model->db($scope);
    }

    /**
     * �л���׺���в�ѯ
     * @access public
     * @param string $suffix �л��ı��׺
     * @return Model
     */
    public static function suffix(string $suffix)
    {
        $model = new static();
        $model->setSuffix($suffix);

        return $model;
    }

    public function __call($method, $args)
    {
        if ('withattr' == strtolower($method)) {
            return call_user_func_array([$this, 'withAttribute'], $args);
        }

        return call_user_func_array([$this->db(), $method], $args);
    }

    public static function __callStatic($method, $args)
    {
        $model = new static();

        return call_user_func_array([$model->db(), $method], $args);
    }

    /**
     * ��������
     * @access public
     */
    public function __destruct()
    {
        if ($this->lazySave) {
            $this->save();
        }
    }
}
