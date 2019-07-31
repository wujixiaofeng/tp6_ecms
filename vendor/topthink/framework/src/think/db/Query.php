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

namespace think\db;

use Closure;
use PDO;
use PDOStatement;
use think\App;
use think\Collection;
use think\Db;
use think\db\exception\BindParamException;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;
use think\exception\PDOException;
use think\Model;
use think\model\Collection as ModelCollection;
use think\model\Relation;
use think\model\relation\OneToOne;
use think\Paginator;

/**
 * ���ݲ�ѯ��
 */
class Query
{
    /**
     * ��ǰ���ݿ����Ӷ���
     * @var Connection
     */
    protected $connection;

    /**
     * ��ǰģ�Ͷ���
     * @var Model
     */
    protected $model;

    /**
     * Db����
     * @var Db
     */
    protected $db;

    /**
     * ��ǰ���ݱ����ƣ�����ǰ׺��
     * @var string
     */
    protected $name = '';

    /**
     * ��ǰ���ݱ�����
     * @var string|array
     */
    protected $pk;

    /**
     * ��ǰ���ݱ�ǰ׺
     * @var string
     */
    protected $prefix = '';

    /**
     * ��ǰ��ѯ����
     * @var array
     */
    protected $options = [];

    /**
     * ��ǰ������
     * @var array
     */
    protected $bind = [];

    /**
     * ���ڲ�ѯ���ʽ
     * @var array
     */
    protected $timeRule = [
        'today'      => ['today', 'tomorrow'],
        'yesterday'  => ['yesterday', 'today'],
        'week'       => ['this week 00:00:00', 'next week 00:00:00'],
        'last week'  => ['last week 00:00:00', 'this week 00:00:00'],
        'month'      => ['first Day of this month 00:00:00', 'first Day of next month 00:00:00'],
        'last month' => ['first Day of last month 00:00:00', 'first Day of this month 00:00:00'],
        'year'       => ['this year 1/1', 'next year 1/1'],
        'last year'  => ['last year 1/1', 'this year 1/1'],
    ];

    /**
     * �ܹ�����
     * @access public
     * @param Connection $connection ���ݿ����Ӷ���
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;

        $this->prefix = $this->connection->getConfig('prefix');
    }

    /**
     * ����һ���µĲ�ѯ����
     * @access public
     * @return Query
     */
    public function newQuery()
    {
        $query = new static($this->connection);

        if ($this->model) {
            $query->model($this->model);
        }

        if (isset($this->options['table'])) {
            $query->table($this->options['table']);
        } else {
            $query->name($this->name);
        }

        $query->setDb($this->db);

        return $query;
    }

    /**
     * ����__call����ʵ��һЩ�����Model����
     * @access public
     * @param string $method ��������
     * @param array  $args   ���ò���
     * @return mixed
     * @throws DbException
     * @throws Exception
     */
    public function __call(string $method, array $args)
    {
        if (strtolower(substr($method, 0, 5)) == 'getby') {
            // ����ĳ���ֶλ�ȡ��¼
            $field = App::parseName(substr($method, 5));
            return $this->where($field, '=', $args[0])->find();
        } elseif (strtolower(substr($method, 0, 10)) == 'getfieldby') {
            // ����ĳ���ֶλ�ȡ��¼��ĳ��ֵ
            $name = App::parseName(substr($method, 10));
            return $this->where($name, '=', $args[0])->value($args[1]);
        } elseif (strtolower(substr($method, 0, 7)) == 'whereor') {
            $name = App::parseName(substr($method, 7));
            array_unshift($args, $name);
            return call_user_func_array([$this, 'whereOr'], $args);
        } elseif (strtolower(substr($method, 0, 5)) == 'where') {
            $name = App::parseName(substr($method, 5));
            array_unshift($args, $name);
            return call_user_func_array([$this, 'where'], $args);
        } elseif ($this->model && method_exists($this->model, 'scope' . $method)) {
            // ��̬����������Χ
            $method = 'scope' . $method;
            array_unshift($args, $this);

            call_user_func_array([$this->model, $method], $args);
            return $this;
        } else {
            throw new Exception('method not exist:' . static::class . '->' . $method);
        }
    }

    /**
     * ��ȡ��ǰ�����ݿ�Connection����
     * @access public
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * ���õ�ǰ�����ݿ�Connection����
     * @access public
     * @param Connection $connection ���ݿ����Ӷ���
     * @return $this
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * ����Db����
     * @access public
     * @param Db $db
     * @return $this
     */
    public function setDb(Db $db)
    {
        $this->db = $db;
        $this->connection->setDb($db);
        return $this;
    }

    /**
     * ��ȡDb����
     * @access public
     * @return Db
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * ָ��ģ��
     * @access public
     * @param Model $model ģ�Ͷ���ʵ��
     * @return $this
     */
    public function model(Model $model)
    {
        $this->model = $model;
        return $this;
    }

    /**
     * ��ȡ��ǰ��ģ�Ͷ���
     * @access public
     * @param bool $clear �Ƿ���Ҫ��ղ�ѯ����
     * @return Model|null
     */
    public function getModel(bool $clear = true)
    {
        return $this->model ? $this->model->setQuery($this, $clear) : null;
    }

    /**
     * ָ����ǰ���ݱ���������ǰ׺��
     * @access public
     * @param string $name ����ǰ׺�����ݱ�����
     * @return $this
     */
    public function name(string $name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * ��ȡ��ǰ�����ݱ�����
     * @access public
     * @return string
     */
    public function getName(): string
    {
        return $this->name ?: $this->model->getName();
    }

    /**
     * ��ȡ���ݿ�����ò���
     * @access public
     * @param string $name ��������
     * @return mixed
     */
    public function getConfig(string $name = '')
    {
        return $this->connection->getConfig($name);
    }

    /**
     * �õ���ǰ����ָ�����Ƶ����ݱ�
     * @access public
     * @param string $name ����ǰ׺�����ݱ�����
     * @return mixed
     */
    public function getTable(string $name = '')
    {
        if (empty($name) && isset($this->options['table'])) {
            return $this->options['table'];
        }

        $name = $name ?: $this->name;

        return $this->prefix . App::parseName($name);
    }

    /**
     * ��ȡ���ݱ��ֶ���Ϣ
     * @access public
     * @param string $tableName ���ݱ���
     * @return array
     */
    public function getTableFields($tableName = ''): array
    {
        if ('' == $tableName) {
            $tableName = $this->getTable();
        }

        return $this->connection->getTableFields($tableName);
    }

    /**
     * �����ֶ�������Ϣ
     * @access public
     * @param array $type �ֶ�������Ϣ
     * @return $this
     */
    public function setFieldType(array $type)
    {
        $this->options['field_type'] = $type;
        return $this;
    }

    /**
     * ��ȡ��ϸ�ֶ�������Ϣ
     * @access public
     * @param string $tableName ���ݱ�����
     * @return array
     */
    public function getFields(string $tableName = ''): array
    {
        return $this->connection->getFields($tableName ?: $this->getTable());
    }

    /**
     * ��ȡ�ֶ�������Ϣ
     * @access public
     * @return array
     */
    public function getFieldsType(): array
    {
        if (!empty($this->options['field_type'])) {
            return $this->options['field_type'];
        }

        return $this->connection->getFieldsType($this->getTable());
    }

    /**
     * ��ȡ�ֶ�������Ϣ
     * @access public
     * @param string $field �ֶ���
     * @return string|null
     */
    public function getFieldType(string $field)
    {
        $fieldType = $this->getFieldsType();

        return $fieldType[$field] ?? null;
    }

    /**
     * ��ȡ�ֶ�������Ϣ
     * @access public
     * @return array
     */
    public function getFieldsBindType(): array
    {
        $fieldType = $this->getFieldsType();

        return array_map([$this->connection, 'getFieldBindType'], $fieldType);
    }

    /**
     * ��ȡ�ֶ�������Ϣ
     * @access public
     * @param string $field �ֶ���
     * @return int
     */
    public function getFieldBindType(string $field): int
    {
        $fieldType = $this->getFieldType($field);

        return $this->connection->getFieldBindType($fieldType ?: '');
    }

    /**
     * ִ�в�ѯ �������ݼ�
     * @access public
     * @param string $sql  sqlָ��
     * @param array  $bind ������
     * @return array
     * @throws BindParamException
     * @throws PDOException
     */
    public function query(string $sql, array $bind = []): array
    {
        return $this->connection->query($this, $sql, $bind, true);
    }

    /**
     * ִ�����
     * @access public
     * @param string $sql  sqlָ��
     * @param array  $bind ������
     * @return int
     * @throws BindParamException
     * @throws PDOException
     */
    public function execute(string $sql, array $bind = []): int
    {
        return $this->connection->execute($this, $sql, $bind, true);
    }

    /**
     * ��ȡ��������ID
     * @access public
     * @param string $sequence ����������
     * @return mixed
     */
    public function getLastInsID(string $sequence = null)
    {
        $insertId = $this->connection->getLastInsID($sequence);

        $pk = $this->getPk();

        if (is_string($pk)) {
            $type = $this->getFieldBindType($pk);

            if (PDO::PARAM_INT == $type) {
                $insertId = (int) $insertId;
            } elseif (Connection::PARAM_FLOAT == $type) {
                $insertId = (float) $insertId;
            }
        }

        return $insertId;
    }

    /**
     * ��ȡ���ػ���Ӱ��ļ�¼��
     * @access public
     * @return integer
     */
    public function getNumRows(): int
    {
        return $this->connection->getNumRows();
    }

    /**
     * ��ȡ���һ�β�ѯ��sql���
     * @access public
     * @return string
     */
    public function getLastSql(): string
    {
        return $this->connection->getLastSql();
    }

    /**
     * ִ�����ݿ�����
     * @access public
     * @param callable $callback ���ݲ��������ص�
     * @return mixed
     */
    public function transaction(callable $callback)
    {
        return $this->connection->transaction($callback);
    }

    /**
     * ��������
     * @access public
     * @return void
     */
    public function startTrans(): void
    {
        $this->connection->startTrans();
    }

    /**
     * ���ڷ��Զ��ύ״̬����Ĳ�ѯ�ύ
     * @access public
     * @return void
     * @throws PDOException
     */
    public function commit(): void
    {
        $this->connection->commit();
    }

    /**
     * ����ع�
     * @access public
     * @return void
     * @throws PDOException
     */
    public function rollback(): void
    {
        $this->connection->rollback();
    }

    /**
     * ������ִ��SQL���
     * �������ָ���Ϊ��execute����
     * @access public
     * @param array $sql SQL������ָ��
     * @return bool
     */
    public function batchQuery(array $sql = []): bool
    {
        return $this->connection->batchQuery($this, $sql);
    }

    /**
     * �õ�ĳ���ֶε�ֵ
     * @access public
     * @param string $field   �ֶ���
     * @param mixed  $default Ĭ��ֵ
     * @return mixed
     */
    public function value(string $field, $default = null)
    {
        return $this->connection->value($this, $field, $default);
    }

    /**
     * �õ�ĳ���е�����
     * @access public
     * @param string $field �ֶ��� ����ֶ��ö��ŷָ�
     * @param string $key   ����
     * @return array
     */
    public function column(string $field, string $key = ''): array
    {
        return $this->connection->column($this, $field, $key);
    }

    /**
     * �ۺϲ�ѯ
     * @access protected
     * @param string     $aggregate �ۺϷ���
     * @param string|Raw $field     �ֶ���
     * @param bool       $force     ǿ��תΪ��������
     * @return mixed
     */
    protected function aggregate(string $aggregate, $field, bool $force = false)
    {
        return $this->connection->aggregate($this, $aggregate, $field, $force);
    }

    /**
     * COUNT��ѯ
     * @access public
     * @param string|Raw $field �ֶ���
     * @return int
     */
    public function count(string $field = '*'): int
    {
        if (!empty($this->options['group'])) {
            // ֧��GROUP
            $options = $this->getOptions();
            $subSql  = $this->options($options)
                ->field('count(' . $field . ') AS think_count')
                ->bind($this->bind)
                ->buildSql();

            $query = $this->newQuery()->table([$subSql => '_group_count_']);

            $count = $query->aggregate('COUNT', '*');
        } else {
            $count = $this->aggregate('COUNT', $field);
        }

        return (int) $count;
    }

    /**
     * SUM��ѯ
     * @access public
     * @param string|Raw $field �ֶ���
     * @return float
     */
    public function sum($field): float
    {
        return $this->aggregate('SUM', $field, true);
    }

    /**
     * MIN��ѯ
     * @access public
     * @param string|Raw $field �ֶ���
     * @param bool       $force ǿ��תΪ��������
     * @return mixed
     */
    public function min($field, bool $force = true)
    {
        return $this->aggregate('MIN', $field, $force);
    }

    /**
     * MAX��ѯ
     * @access public
     * @param string|Raw $field �ֶ���
     * @param bool       $force ǿ��תΪ��������
     * @return mixed
     */
    public function max($field, bool $force = true)
    {
        return $this->aggregate('MAX', $field, $force);
    }

    /**
     * AVG��ѯ
     * @access public
     * @param string|Raw $field �ֶ���
     * @return float
     */
    public function avg($field): float
    {
        return $this->aggregate('AVG', $field, true);
    }

    /**
     * ��ѯSQL��װ join
     * @access public
     * @param mixed  $join      �����ı���
     * @param mixed  $condition ����
     * @param string $type      JOIN����
     * @param array  $bind      ������
     * @return $this
     */
    public function join($join, string $condition = null, string $type = 'INNER', array $bind = [])
    {
        $table = $this->getJoinTable($join);

        if (!empty($bind) && $condition) {
            $this->bindParams($condition, $bind);
        }

        $this->options['join'][] = [$table, strtoupper($type), $condition];

        return $this;
    }

    /**
     * LEFT JOIN
     * @access public
     * @param mixed $join      �����ı���
     * @param mixed $condition ����
     * @param array $bind      ������
     * @return $this
     */
    public function leftJoin($join, string $condition = null, array $bind = [])
    {
        return $this->join($join, $condition, 'LEFT', $bind);
    }

    /**
     * RIGHT JOIN
     * @access public
     * @param mixed $join      �����ı���
     * @param mixed $condition ����
     * @param array $bind      ������
     * @return $this
     */
    public function rightJoin($join, string $condition = null, array $bind = [])
    {
        return $this->join($join, $condition, 'RIGHT', $bind);
    }

    /**
     * FULL JOIN
     * @access public
     * @param mixed $join      �����ı���
     * @param mixed $condition ����
     * @param array $bind      ������
     * @return $this
     */
    public function fullJoin($join, string $condition = null, array $bind = [])
    {
        return $this->join($join, $condition, 'FULL');
    }

    /**
     * ��ȡJoin���������� ֧��
     * ['prefix_table�����Ӳ�ѯ'=>'alias'] 'table alias'
     * @access protected
     * @param array|string|Raw $join  JION����
     * @param string           $alias ����
     * @return string|array
     */
    protected function getJoinTable($join, &$alias = null)
    {
        if (is_array($join)) {
            $table = $join;
            $alias = array_shift($join);
            return $table;
        } elseif ($join instanceof Raw) {
            return $join;
        }

        $join = trim($join);

        if (false !== strpos($join, '(')) {
            // ʹ���Ӳ�ѯ
            $table = $join;
        } else {
            // ʹ�ñ���
            if (strpos($join, ' ')) {
                // ʹ�ñ���
                list($table, $alias) = explode(' ', $join);
            } else {
                $table = $join;
                if (false === strpos($join, '.')) {
                    $alias = $join;
                }
            }

            if ($this->prefix && false === strpos($table, '.') && 0 !== strpos($table, $this->prefix)) {
                $table = $this->getTable($table);
            }
        }

        if (!empty($alias) && $table != $alias) {
            $table = [$table => $alias];
        }

        return $table;
    }

    /**
     * ��ѯSQL��װ union
     * @access public
     * @param mixed   $union UNION
     * @param boolean $all   �Ƿ�����UNION ALL
     * @return $this
     */
    public function union($union, bool $all = false)
    {
        $this->options['union']['type'] = $all ? 'UNION ALL' : 'UNION';

        if (is_array($union)) {
            $this->options['union'] = array_merge($this->options['union'], $union);
        } else {
            $this->options['union'][] = $union;
        }

        return $this;
    }

    /**
     * ��ѯSQL��װ union all
     * @access public
     * @param mixed $union UNION����
     * @return $this
     */
    public function unionAll($union)
    {
        return $this->union($union, true);
    }

    /**
     * ָ����ѯ�ֶ�
     * @access public
     * @param mixed $field �ֶ���Ϣ
     * @return $this
     */
    public function field($field)
    {
        if (empty($field)) {
            return $this;
        } elseif ($field instanceof Raw) {
            $this->options['field'][] = $field;
            return $this;
        }

        if (is_string($field)) {
            if (preg_match('/[\<\'\"\(]/', $field)) {
                return $this->fieldRaw($field);
            }

            $field = array_map('trim', explode(',', $field));
        }

        if (true === $field) {
            // ��ȡȫ���ֶ�
            $fields = $this->getTableFields();
            $field  = $fields ?: ['*'];
        }

        if (isset($this->options['field'])) {
            $field = array_merge((array) $this->options['field'], $field);
        }

        $this->options['field'] = array_unique($field);

        return $this;
    }

    /**
     * ָ��Ҫ�ų��Ĳ�ѯ�ֶ�
     * @access public
     * @param array|string $field Ҫ�ų����ֶ�
     * @return $this
     */
    public function withoutField($field)
    {
        if (empty($field)) {
            return $this;
        }

        if (is_string($field)) {
            $field = array_map('trim', explode(',', $field));
        }

        // �ֶ��ų�
        $fields = $this->getTableFields();
        $field  = $fields ? array_diff($fields, $field) : $field;

        if (isset($this->options['field'])) {
            $field = array_merge((array) $this->options['field'], $field);
        }

        $this->options['field'] = array_unique($field);

        return $this;
    }

    /**
     * ָ���������ݱ�Ĳ�ѯ�ֶ�
     * @access public
     * @param mixed   $field     �ֶ���Ϣ
     * @param string  $tableName ���ݱ���
     * @param string  $prefix    �ֶ�ǰ׺
     * @param string  $alias     ����ǰ׺
     * @return $this
     */
    public function tableField($field, string $tableName, string $prefix = '', string $alias = '')
    {
        if (empty($field)) {
            return $this;
        }

        if (is_string($field)) {
            $field = array_map('trim', explode(',', $field));
        }

        if (true === $field) {
            // ��ȡȫ���ֶ�
            $fields = $this->getTableFields($tableName);
            $field  = $fields ?: ['*'];
        }

        // ���ͳһ��ǰ׺
        $prefix = $prefix ?: $tableName;
        foreach ($field as $key => &$val) {
            if (is_numeric($key) && $alias) {
                $field[$prefix . '.' . $val] = $alias . $val;
                unset($field[$key]);
            } elseif (is_numeric($key)) {
                $val = $prefix . '.' . $val;
            }
        }

        if (isset($this->options['field'])) {
            $field = array_merge((array) $this->options['field'], $field);
        }

        $this->options['field'] = array_unique($field);

        return $this;
    }

    /**
     * ���ʽ��ʽָ����ѯ�ֶ�
     * @access public
     * @param string $field �ֶ���
     * @return $this
     */
    public function fieldRaw(string $field)
    {
        $this->options['field'][] = new Raw($field);

        return $this;
    }

    /**
     * ��������
     * @access public
     * @param array $data ����
     * @return $this
     */
    public function data(array $data)
    {
        $this->options['data'] = $data;

        return $this;
    }

    /**
     * �ֶ�ֵ����
     * @access public
     * @param string  $field    �ֶ���
     * @param float   $step     ����ֵ
     * @param integer $lazyTime ��ʱʱ��(s)
     * @param string  $op       INC/DEC
     * @return $this
     */
    public function inc(string $field, float $step = 1, int $lazyTime = 0, string $op = 'INC')
    {
        if ($lazyTime > 0) {
            // �ӳ�д��
            $condition = $this->options['where'] ?? [];

            $guid = md5($this->getTable() . '_' . $field . '_' . serialize($condition));
            $step = $this->connection->lazyWrite($op, $guid, $step, $lazyTime);

            if (false === $step) {
                return $this;
            }

            $op = 'INC';
        }

        $this->options['data'][$field] = [$op, $step];

        return $this;
    }

    /**
     * �ֶ�ֵ����
     * @access public
     * @param string  $field    �ֶ���
     * @param float   $step     ����ֵ
     * @param integer $lazyTime ��ʱʱ��(s)
     * @return $this
     */
    public function dec(string $field, float $step = 1, int $lazyTime = 0)
    {
        return $this->inc($field, $step, $lazyTime, 'DEC');
    }

    /**
     * ʹ�ñ��ʽ��������
     * @access public
     * @param string $field �ֶ���
     * @param string $value �ֶ�ֵ
     * @return $this
     */
    public function exp(string $field, string $value)
    {
        $this->options['data'][$field] = new Raw($value);
        return $this;
    }

    /**
     * ָ��JOIN��ѯ�ֶ�
     * @access public
     * @param string|array $join  ���ݱ�
     * @param string|array $field ��ѯ�ֶ�
     * @param string       $on    JOIN����
     * @param string       $type  JOIN����
     * @param array        $bind  ������
     * @return $this
     */
    public function view($join, $field = true, $on = null, string $type = 'INNER', array $bind = [])
    {
        $this->options['view'] = true;

        $fields = [];
        $table  = $this->getJoinTable($join, $alias);

        if (true === $field) {
            $fields = $alias . '.*';
        } else {
            if (is_string($field)) {
                $field = explode(',', $field);
            }

            foreach ($field as $key => $val) {
                if (is_numeric($key)) {
                    $fields[] = $alias . '.' . $val;

                    $this->options['map'][$val] = $alias . '.' . $val;
                } else {
                    if (preg_match('/[,=\.\'\"\(\s]/', $key)) {
                        $name = $key;
                    } else {
                        $name = $alias . '.' . $key;
                    }

                    $fields[] = $name . ' AS ' . $val;

                    $this->options['map'][$val] = $name;
                }
            }
        }

        $this->field($fields);

        if ($on) {
            $this->join($table, $on, $type, $bind);
        } else {
            $this->table($table);
        }

        return $this;
    }

    /**
     * ָ��AND��ѯ����
     * @access public
     * @param mixed $field     ��ѯ�ֶ�
     * @param mixed $op        ��ѯ���ʽ
     * @param mixed $condition ��ѯ����
     * @return $this
     */
    public function where($field, $op = null, $condition = null)
    {
        if ($field instanceof $this) {
            $this->parseQueryWhere($field);
            return $this;
        }

        $param = func_get_args();
        array_shift($param);
        return $this->parseWhereExp('AND', $field, $op, $condition, $param);
    }

    /**
     * ����Query�����ѯ����
     * @access public
     * @param Query $query ��ѯ����
     * @return void
     */
    protected function parseQueryWhere(Query $query): void
    {
        $this->options['where'] = $query->getOptions('where');

        if ($query->getOptions('via')) {
            $via = $query->getOptions('via');
            foreach ($this->options['where'] as $logic => &$where) {
                foreach ($where as $key => &$val) {
                    if (is_array($val) && !strpos($val[0], '.')) {
                        $val[0] = $via . '.' . $val[0];
                    }
                }
            }
        }

        $this->bind($query->getBind(false));
    }

    /**
     * ָ��OR��ѯ����
     * @access public
     * @param mixed $field     ��ѯ�ֶ�
     * @param mixed $op        ��ѯ���ʽ
     * @param mixed $condition ��ѯ����
     * @return $this
     */
    public function whereOr($field, $op = null, $condition = null)
    {
        $param = func_get_args();
        array_shift($param);
        return $this->parseWhereExp('OR', $field, $op, $condition, $param);
    }

    /**
     * ָ��XOR��ѯ����
     * @access public
     * @param mixed $field     ��ѯ�ֶ�
     * @param mixed $op        ��ѯ���ʽ
     * @param mixed $condition ��ѯ����
     * @return $this
     */
    public function whereXor($field, $op = null, $condition = null)
    {
        $param = func_get_args();
        array_shift($param);
        return $this->parseWhereExp('XOR', $field, $op, $condition, $param);
    }

    /**
     * ָ��Null��ѯ����
     * @access public
     * @param mixed  $field ��ѯ�ֶ�
     * @param string $logic ��ѯ�߼� and or xor
     * @return $this
     */
    public function whereNull(string $field, string $logic = 'AND')
    {
        return $this->parseWhereExp($logic, $field, 'NULL', null, [], true);
    }

    /**
     * ָ��NotNull��ѯ����
     * @access public
     * @param mixed  $field ��ѯ�ֶ�
     * @param string $logic ��ѯ�߼� and or xor
     * @return $this
     */
    public function whereNotNull(string $field, string $logic = 'AND')
    {
        return $this->parseWhereExp($logic, $field, 'NOTNULL', null, [], true);
    }

    /**
     * ָ��Exists��ѯ����
     * @access public
     * @param mixed  $condition ��ѯ����
     * @param string $logic     ��ѯ�߼� and or xor
     * @return $this
     */
    public function whereExists($condition, string $logic = 'AND')
    {
        if (is_string($condition)) {
            $condition = new Raw($condition);
        }

        $this->options['where'][strtoupper($logic)][] = ['', 'EXISTS', $condition];
        return $this;
    }

    /**
     * ָ��NotExists��ѯ����
     * @access public
     * @param mixed  $condition ��ѯ����
     * @param string $logic     ��ѯ�߼� and or xor
     * @return $this
     */
    public function whereNotExists($condition, string $logic = 'AND')
    {
        if (is_string($condition)) {
            $condition = new Raw($condition);
        }

        $this->options['where'][strtoupper($logic)][] = ['', 'NOT EXISTS', $condition];
        return $this;
    }

    /**
     * ָ��In��ѯ����
     * @access public
     * @param mixed  $field     ��ѯ�ֶ�
     * @param mixed  $condition ��ѯ����
     * @param string $logic     ��ѯ�߼� and or xor
     * @return $this
     */
    public function whereIn(string $field, $condition, string $logic = 'AND')
    {
        return $this->parseWhereExp($logic, $field, 'IN', $condition, [], true);
    }

    /**
     * ָ��NotIn��ѯ����
     * @access public
     * @param mixed  $field     ��ѯ�ֶ�
     * @param mixed  $condition ��ѯ����
     * @param string $logic     ��ѯ�߼� and or xor
     * @return $this
     */
    public function whereNotIn(string $field, $condition, string $logic = 'AND')
    {
        return $this->parseWhereExp($logic, $field, 'NOT IN', $condition, [], true);
    }

    /**
     * ָ��Like��ѯ����
     * @access public
     * @param mixed  $field     ��ѯ�ֶ�
     * @param mixed  $condition ��ѯ����
     * @param string $logic     ��ѯ�߼� and or xor
     * @return $this
     */
    public function whereLike(string $field, $condition, string $logic = 'AND')
    {
        return $this->parseWhereExp($logic, $field, 'LIKE', $condition, [], true);
    }

    /**
     * ָ��NotLike��ѯ����
     * @access public
     * @param mixed  $field     ��ѯ�ֶ�
     * @param mixed  $condition ��ѯ����
     * @param string $logic     ��ѯ�߼� and or xor
     * @return $this
     */
    public function whereNotLike(string $field, $condition, string $logic = 'AND')
    {
        return $this->parseWhereExp($logic, $field, 'NOT LIKE', $condition, [], true);
    }

    /**
     * ָ��Between��ѯ����
     * @access public
     * @param mixed  $field     ��ѯ�ֶ�
     * @param mixed  $condition ��ѯ����
     * @param string $logic     ��ѯ�߼� and or xor
     * @return $this
     */
    public function whereBetween(string $field, $condition, string $logic = 'AND')
    {
        return $this->parseWhereExp($logic, $field, 'BETWEEN', $condition, [], true);
    }

    /**
     * ָ��NotBetween��ѯ����
     * @access public
     * @param mixed  $field     ��ѯ�ֶ�
     * @param mixed  $condition ��ѯ����
     * @param string $logic     ��ѯ�߼� and or xor
     * @return $this
     */
    public function whereNotBetween(string $field, $condition, string $logic = 'AND')
    {
        return $this->parseWhereExp($logic, $field, 'NOT BETWEEN', $condition, [], true);
    }

    /**
     * ָ��FIND_IN_SET��ѯ����
     * @access public
     * @param mixed  $field     ��ѯ�ֶ�
     * @param mixed  $condition ��ѯ����
     * @param string $logic     ��ѯ�߼� and or xor
     * @return $this
     */
    public function whereFindInSet(string $field, $condition, string $logic = 'AND')
    {
        return $this->parseWhereExp($logic, $field, 'FIND IN SET', $condition, [], true);
    }

    /**
     * �Ƚ������ֶ�
     * @access public
     * @param string $field1   ��ѯ�ֶ�
     * @param string $operator �Ƚϲ�����
     * @param string $field2   �Ƚ��ֶ�
     * @param string $logic    ��ѯ�߼� and or xor
     * @return $this
     */
    public function whereColumn(string $field1, string $operator, string $field2 = null, string $logic = 'AND')
    {
        if (is_null($field2)) {
            $field2   = $operator;
            $operator = '=';
        }

        return $this->parseWhereExp($logic, $field1, 'COLUMN', [$operator, $field2], [], true);
    }

    /**
     * ������ɾ���ֶμ�����
     * @access public
     * @param string $field     ��ѯ�ֶ�
     * @param mixed  $condition ��ѯ����
     * @return $this
     */
    public function useSoftDelete(string $field, $condition = null)
    {
        if ($field) {
            $this->options['soft_delete'] = [$field, $condition];
        }

        return $this;
    }

    /**
     * ָ��Exp��ѯ����
     * @access public
     * @param mixed  $field ��ѯ�ֶ�
     * @param string $where ��ѯ����
     * @param array  $bind  ������
     * @param string $logic ��ѯ�߼� and or xor
     * @return $this
     */
    public function whereExp(string $field, string $where, array $bind = [], string $logic = 'AND')
    {
        if (!empty($bind)) {
            $this->bindParams($where, $bind);
        }

        $this->options['where'][$logic][] = [$field, 'EXP', new Raw($where)];

        return $this;
    }

    /**
     * ָ���ֶ�Raw��ѯ
     * @access public
     * @param string $field     ��ѯ�ֶα��ʽ
     * @param mixed  $op        ��ѯ���ʽ
     * @param string $condition ��ѯ����
     * @param string $logic     ��ѯ�߼� and or xor
     * @return $this
     */
    public function whereFieldRaw(string $field, $op, $condition = null, string $logic = 'AND')
    {
        if (is_null($condition)) {
            $condition = $op;
            $op        = '=';
        }

        $this->options['where'][$logic][] = [new Raw($field), $op, $condition];
        return $this;
    }

    /**
     * ָ�����ʽ��ѯ����
     * @access public
     * @param string $where ��ѯ����
     * @param array  $bind  ������
     * @param string $logic ��ѯ�߼� and or xor
     * @return $this
     */
    public function whereRaw(string $where, array $bind = [], string $logic = 'AND')
    {
        if (!empty($bind)) {
            $this->bindParams($where, $bind);
        }

        $this->options['where'][$logic][] = new Raw($where);

        return $this;
    }

    /**
     * ָ�����ʽ��ѯ���� OR
     * @access public
     * @param string $where ��ѯ����
     * @param array  $bind  ������
     * @return $this
     */
    public function whereOrRaw(string $where, array $bind = [])
    {
        return $this->whereRaw($where, $bind, 'OR');
    }

    /**
     * ������ѯ���ʽ
     * @access protected
     * @param string $logic     ��ѯ�߼� and or xor
     * @param mixed  $field     ��ѯ�ֶ�
     * @param mixed  $op        ��ѯ���ʽ
     * @param mixed  $condition ��ѯ����
     * @param array  $param     ��ѯ����
     * @param bool   $strict    �ϸ�ģʽ
     * @return $this
     */
    protected function parseWhereExp(string $logic, $field, $op, $condition, array $param = [], bool $strict = false)
    {
        $logic = strtoupper($logic);

        if (is_string($field) && !empty($this->options['via']) && false === strpos($field, '.')) {
            $field = $this->options['via'] . '.' . $field;
        }

        if ($field instanceof Raw) {
            return $this->whereRaw($field, is_array($op) ? $op : [], $logic);
        } elseif ($strict) {
            // ʹ���ϸ�ģʽ��ѯ
            if ('=' == $op) {
                $where = $this->whereEq($field, $condition);
            } else {
                $where = [$field, $op, $condition, $logic];
            }
        } elseif (is_array($field)) {
            // ��������������ѯ
            return $this->parseArrayWhereItems($field, $logic);
        } elseif ($field instanceof Closure) {
            $where = $field;
        } elseif (is_string($field)) {
            if (preg_match('/[,=\<\'\"\(\s]/', $field)) {
                return $this->whereRaw($field, is_array($op) ? $op : [], $logic);
            } elseif (is_string($op) && strtolower($op) == 'exp') {
                $bind = isset($param[2]) && is_array($param[2]) ? $param[2] : [];
                return $this->whereExp($field, $condition, $bind, $logic);
            }

            $where = $this->parseWhereItem($logic, $field, $op, $condition, $param);
        }

        if (!empty($where)) {
            $this->options['where'][$logic][] = $where;
        }

        return $this;
    }

    /**
     * ������ѯ���ʽ
     * @access protected
     * @param string $logic     ��ѯ�߼� and or xor
     * @param mixed  $field     ��ѯ�ֶ�
     * @param mixed  $op        ��ѯ���ʽ
     * @param mixed  $condition ��ѯ����
     * @param array  $param     ��ѯ����
     * @return array
     */
    protected function parseWhereItem(string $logic, $field, $op, $condition, array $param = []): array
    {
        if (is_array($op)) {
            // ͬһ�ֶζ�������ѯ
            array_unshift($param, $field);
            $where = $param;
        } elseif ($field && is_null($condition)) {
            if (is_string($op) && in_array(strtoupper($op), ['NULL', 'NOTNULL', 'NOT NULL'], true)) {
                // null��ѯ
                $where = [$field, $op, ''];
            } elseif ('=' === $op || is_null($op)) {
                $where = [$field, 'NULL', ''];
            } elseif ('<>' === $op) {
                $where = [$field, 'NOTNULL', ''];
            } else {
                // �ֶ���Ȳ�ѯ
                $where = $this->whereEq($field, $op);
            }
        } elseif (in_array(strtoupper($op), ['EXISTS', 'NOT EXISTS', 'NOTEXISTS'], true)) {
            $where = [$field, $op, is_string($condition) ? new Raw($condition) : $condition];
        } else {
            $where = $field ? [$field, $op, $condition, $param[2] ?? null] : [];
        }

        return $where;
    }

    /**
     * ��Ȳ�ѯ����������
     * @access protected
     * @param string $field �ֶ���
     * @param mixed  $value �ֶ�ֵ
     * @return array
     */
    protected function whereEq(string $field, $value): array
    {
        if ($this->getPk() == $field) {
            $this->options['key'] = $value;
        }

        return [$field, '=', $value];
    }

    /**
     * ����������ѯ
     * @access protected
     * @param array  $field ������ѯ
     * @param string $logic ��ѯ�߼� and or xor
     * @return $this
     */
    protected function parseArrayWhereItems(array $field, string $logic)
    {
        if (key($field) !== 0) {
            $where = [];
            foreach ($field as $key => $val) {
                if ($val instanceof Raw) {
                    $where[] = [$key, 'exp', $val];
                } else {
                    $where[] = is_null($val) ? [$key, 'NULL', ''] : [$key, is_array($val) ? 'IN' : '=', $val];
                }
            }
        } else {
            // ����������ѯ
            $where = $field;
        }

        if (!empty($where)) {
            $this->options['where'][$logic] = isset($this->options['where'][$logic]) ?
            array_merge($this->options['where'][$logic], $where) : $where;
        }

        return $this;
    }

    /**
     * ȥ��ĳ����ѯ����
     * @access public
     * @param string $field ��ѯ�ֶ�
     * @param string $logic ��ѯ�߼� and or xor
     * @return $this
     */
    public function removeWhereField(string $field, string $logic = 'AND')
    {
        $logic = strtoupper($logic);

        if (isset($this->options['where'][$logic])) {
            foreach ($this->options['where'][$logic] as $key => $val) {
                if (is_array($val) && $val[0] == $field) {
                    unset($this->options['where'][$logic][$key]);
                }
            }
        }

        return $this;
    }

    /**
     * ȥ����ѯ����
     * @access public
     * @param string $option ������ ����ȥ�����в���
     * @return $this
     */
    public function removeOption(string $option = '')
    {
        if ('' === $option) {
            $this->options = [];
            $this->bind    = [];
        } elseif (isset($this->options[$option])) {
            unset($this->options[$option]);
        }

        return $this;
    }

    /**
     * ������ѯ
     * @access public
     * @param mixed         $condition ����������֧�ֱհ���
     * @param Closure|array $query     ����������ִ�еĲ�ѯ���ʽ���հ������飩
     * @param Closure|array $otherwise ������������ִ��
     * @return $this
     */
    public function when($condition, $query, $otherwise = null)
    {
        if ($condition instanceof Closure) {
            $condition = $condition($this);
        }

        if ($condition) {
            if ($query instanceof Closure) {
                $query($this, $condition);
            } elseif (is_array($query)) {
                $this->where($query);
            }
        } elseif ($otherwise) {
            if ($otherwise instanceof Closure) {
                $otherwise($this, $condition);
            } elseif (is_array($otherwise)) {
                $this->where($otherwise);
            }
        }

        return $this;
    }

    /**
     * ָ����ѯ����
     * @access public
     * @param int $offset ��ʼλ��
     * @param int $length ��ѯ����
     * @return $this
     */
    public function limit(int $offset, int $length = null)
    {
        $this->options['limit'] = $offset . ($length ? ',' . $length : '');

        return $this;
    }

    /**
     * ָ����ҳ
     * @access public
     * @param int $page     ҳ��
     * @param int $listRows ÿҳ����
     * @return $this
     */
    public function page(int $page, int $listRows = null)
    {
        $this->options['page'] = [$page, $listRows];

        return $this;
    }

    /**
     * ��ҳ��ѯ
     * @access public
     * @param int|array $listRows ÿҳ���� �����ʾ���ò���
     * @param int|bool  $simple   �Ƿ���ģʽ�����ܼ�¼��
     * @param array     $config   ���ò���
     * @return Paginator
     * @throws DbException
     */
    public function paginate($listRows = null, $simple = false, $config = [])
    {
        if (is_int($simple)) {
            $total  = $simple;
            $simple = false;
        }

        $defaultConfig = [
            'query'     => [], //url�������
            'fragment'  => '', //urlê��
            'var_page'  => 'page', //��ҳ����
            'list_rows' => 15, //ÿҳ����
        ];

        if (is_array($listRows)) {
            $config   = array_merge($defaultConfig, $listRows);
            $listRows = intval($config['list_rows']);
        } else {
            $config   = array_merge($defaultConfig, $config);
            $listRows = intval($listRows ?: $config['list_rows']);
        }

        $page = isset($config['page']) ? (int) $config['page'] : Paginator::getCurrentPage($config['var_page']);

        $page = $page < 1 ? 1 : $page;

        $config['path'] = $config['path'] ?? Paginator::getCurrentPath();

        if (!isset($total) && !$simple) {
            $options = $this->getOptions();

            unset($this->options['order'], $this->options['limit'], $this->options['page'], $this->options['field']);

            $bind    = $this->bind;
            $total   = $this->count();
            $results = $this->options($options)->bind($bind)->page($page, $listRows)->select();
        } elseif ($simple) {
            $results = $this->limit(($page - 1) * $listRows, $listRows + 1)->select();
            $total   = null;
        } else {
            $results = $this->page($page, $listRows)->select();
        }

        $this->removeOption('limit');
        $this->removeOption('page');

        return Paginator::make($results, $listRows, $page, $total, $simple, $config);
    }

    /**
     * ���ʽ��ʽָ����ǰ���������ݱ�
     * @access public
     * @param mixed $table ����
     * @return $this
     */
    public function tableRaw(string $table)
    {
        $this->options['table'] = new Raw($table);

        return $this;
    }

    /**
     * ָ����ǰ���������ݱ�
     * @access public
     * @param mixed $table ����
     * @return $this
     */
    public function table($table)
    {
        if (is_string($table)) {
            if (strpos($table, ')')) {
                // �Ӳ�ѯ
            } elseif (false === strpos($table, ',')) {
                if (strpos($table, ' ')) {
                    list($item, $alias) = explode(' ', $table);
                    $table              = [];
                    $this->alias([$item => $alias]);
                    $table[$item] = $alias;
                }
            } else {
                $tables = explode(',', $table);
                $table  = [];

                foreach ($tables as $item) {
                    $item = trim($item);
                    if (strpos($item, ' ')) {
                        list($item, $alias) = explode(' ', $item);
                        $this->alias([$item => $alias]);
                        $table[$item] = $alias;
                    } else {
                        $table[] = $item;
                    }
                }
            }
        } elseif (is_array($table)) {
            $tables = $table;
            $table  = [];

            foreach ($tables as $key => $val) {
                if (is_numeric($key)) {
                    $table[] = $val;
                } else {
                    $this->alias([$key => $val]);
                    $table[$key] = $val;
                }
            }
        }

        $this->options['table'] = $table;

        return $this;
    }

    /**
     * USING֧�� ���ڶ��ɾ��
     * @access public
     * @param mixed $using USING
     * @return $this
     */
    public function using($using)
    {
        $this->options['using'] = $using;
        return $this;
    }

    /**
     * �洢���̵���
     * @access public
     * @param bool $procedure �Ƿ�Ϊ�洢���̲�ѯ
     * @return $this
     */
    public function procedure(bool $procedure = true)
    {
        $this->options['procedure'] = $procedure;
        return $this;
    }

    /**
     * �Ƿ������ؿ����ݣ����ģ�ͣ�
     * @access public
     * @param bool $allowEmpty �Ƿ�����Ϊ��
     * @return $this
     */
    public function allowEmpty(bool $allowEmpty = true)
    {
        $this->options['allow_empty'] = $allowEmpty;
        return $this;
    }

    /**
     * ָ������ order('id','desc') ���� order(['id'=>'desc','create_time'=>'desc'])
     * @access public
     * @param string|array|Raw $field �����ֶ�
     * @param string           $order ����
     * @return $this
     */
    public function order($field, string $order = '')
    {
        if (empty($field)) {
            return $this;
        } elseif ($field instanceof Raw) {
            $this->options['order'][] = $field;
            return $this;
        }

        if (is_string($field)) {
            if (!empty($this->options['via'])) {
                $field = $this->options['via'] . '.' . $field;
            }
            if (strpos($field, ',')) {
                $field = array_map('trim', explode(',', $field));
            } else {
                $field = empty($order) ? $field : [$field => $order];
            }
        } elseif (!empty($this->options['via'])) {
            foreach ($field as $key => $val) {
                if (is_numeric($key)) {
                    $field[$key] = $this->options['via'] . '.' . $val;
                } else {
                    $field[$this->options['via'] . '.' . $key] = $val;
                    unset($field[$key]);
                }
            }
        }

        if (!isset($this->options['order'])) {
            $this->options['order'] = [];
        }

        if (is_array($field)) {
            $this->options['order'] = array_merge($this->options['order'], $field);
        } else {
            $this->options['order'][] = $field;
        }

        return $this;
    }

    /**
     * ���ʽ��ʽָ��Field����
     * @access public
     * @param string $field �����ֶ�
     * @param array  $bind  ������
     * @return $this
     */
    public function orderRaw(string $field, array $bind = [])
    {
        if (!empty($bind)) {
            $this->bindParams($field, $bind);
        }

        $this->options['order'][] = new Raw($field);

        return $this;
    }

    /**
     * ָ��Field���� orderField('id',[1,2,3],'desc')
     * @access public
     * @param string $field  �����ֶ�
     * @param array  $values ����ֵ
     * @param string $order  ���� desc/asc
     * @return $this
     */
    public function orderField(string $field, array $values, string $order = '')
    {
        if (!empty($values)) {
            $values['sort'] = $order;

            $this->options['order'][$field] = $values;
        }

        return $this;
    }

    /**
     * �������
     * @access public
     * @return $this
     */
    public function orderRand()
    {
        $this->options['order'][] = '[rand]';
        return $this;
    }

    /**
     * ��ѯ����
     * @access public
     * @param mixed             $key    ����key
     * @param integer|\DateTime $expire ������Ч��
     * @param string            $tag    �����ǩ
     * @return $this
     */
    public function cache($key = true, $expire = null, string $tag = null)
    {
        if (false === $key) {
            return $this;
        }

        if ($key instanceof \DateTimeInterface || $key instanceof \DateInterval || (is_int($key) && is_null($expire))) {
            $expire = $key;
            $key    = true;
        }

        $this->options['cache'] = [$key, $expire, $tag];

        return $this;
    }

    /**
     * ָ��group��ѯ
     * @access public
     * @param string|array $group GROUP
     * @return $this
     */
    public function group($group)
    {
        $this->options['group'] = $group;
        return $this;
    }

    /**
     * ָ��having��ѯ
     * @access public
     * @param string $having having
     * @return $this
     */
    public function having(string $having)
    {
        $this->options['having'] = $having;
        return $this;
    }

    /**
     * ָ����ѯlock
     * @access public
     * @param bool|string $lock �Ƿ�lock
     * @return $this
     */
    public function lock($lock = false)
    {
        $this->options['lock'] = $lock;

        if ($lock) {
            $this->options['master'] = true;
        }

        return $this;
    }

    /**
     * ָ��distinct��ѯ
     * @access public
     * @param bool $distinct �Ƿ�Ψһ
     * @return $this
     */
    public function distinct(bool $distinct = true)
    {
        $this->options['distinct'] = $distinct;
        return $this;
    }

    /**
     * ָ�����ݱ����
     * @access public
     * @param array|string $alias ���ݱ����
     * @return $this
     */
    public function alias($alias)
    {
        if (is_array($alias)) {
            $this->options['alias'] = $alias;
        } else {
            $table = $this->getTable();

            $this->options['alias'][$table] = $alias;
        }

        return $this;
    }

    /**
     * ָ��ǿ������
     * @access public
     * @param string $force ��������
     * @return $this
     */
    public function force(string $force)
    {
        $this->options['force'] = $force;
        return $this;
    }

    /**
     * ��ѯע��
     * @access public
     * @param string $comment ע��
     * @return $this
     */
    public function comment(string $comment)
    {
        $this->options['comment'] = $comment;
        return $this;
    }

    /**
     * ��ȡִ�е�SQL����������ʵ�ʵĲ�ѯ
     * @access public
     * @param bool $fetch �Ƿ񷵻�sql
     * @return $this|Fetch
     */
    public function fetchSql(bool $fetch = true)
    {
        $this->options['fetch_sql'] = $fetch;

        if ($fetch) {
            return new Fetch($this);
        }

        return $this;
    }

    /**
     * ���ô�����������ȡ����
     * @access public
     * @param bool $readMaster �Ƿ������������ȡ
     * @return $this
     */
    public function master(bool $readMaster = true)
    {
        $this->options['master'] = $readMaster;
        return $this;
    }

    /**
     * �����Ƿ��ϸ����ֶ���
     * @access public
     * @param bool $strict �Ƿ��ϸ����ֶ�
     * @return $this
     */
    public function strict(bool $strict = true)
    {
        $this->options['strict'] = $strict;
        return $this;
    }

    /**
     * ���ò�ѯ���ݲ������Ƿ��׳��쳣
     * @access public
     * @param bool $fail ���ݲ������Ƿ��׳��쳣
     * @return $this
     */
    public function failException(bool $fail = true)
    {
        $this->options['fail'] = $fail;
        return $this;
    }

    /**
     * ��������������
     * @access public
     * @param string $sequence ����������
     * @return $this
     */
    public function sequence(string $sequence = null)
    {
        $this->options['sequence'] = $sequence;
        return $this;
    }

    /**
     * �����Ƿ�REPLACE
     * @access public
     * @param bool $replace �Ƿ�ʹ��REPLACEд������
     * @return $this
     */
    public function replace(bool $replace = true)
    {
        $this->options['replace'] = $replace;
        return $this;
    }

    /**
     * ���õ�ǰ��ѯ���ڵķ���
     * @access public
     * @param string|array $partition ��������
     * @return $this
     */
    public function partition($partition)
    {
        $this->options['partition'] = $partition;
        return $this;
    }

    /**
     * ����DUPLICATE
     * @access public
     * @param array|string|Raw $duplicate DUPLICATE��Ϣ
     * @return $this
     */
    public function duplicate($duplicate)
    {
        $this->options['duplicate'] = $duplicate;
        return $this;
    }

    /**
     * ���ò�ѯ�Ķ������
     * @access public
     * @param string $extra ������Ϣ
     * @return $this
     */
    public function extra(string $extra)
    {
        $this->options['extra'] = $extra;
        return $this;
    }

    /**
     * ������Ҫ���ص��������
     * @access public
     * @param array $hidden ��Ҫ���ص��ֶ���
     * @return $this
     */
    public function hidden(array $hidden)
    {
        $this->options['hidden'] = $hidden;
        return $this;
    }

    /**
     * ������Ҫ���������
     * @access public
     * @param array $visible ��Ҫ���������
     * @return $this
     */
    public function visible(array $visible)
    {
        $this->options['visible'] = $visible;
        return $this;
    }

    /**
     * ������Ҫ׷�����������
     * @access public
     * @param array $append ��Ҫ׷�ӵ�����
     * @return $this
     */
    public function append(array $append)
    {
        $this->options['append'] = $append;
        return $this;
    }

    /**
     * ����JSON�ֶ���Ϣ
     * @access public
     * @param array $json  JSON�ֶ�
     * @param bool  $assoc �Ƿ�ȡ������
     * @return $this
     */
    public function json(array $json = [], bool $assoc = false)
    {
        $this->options['json']       = $json;
        $this->options['json_assoc'] = $assoc;
        return $this;
    }

    /**
     * ��Ӳ�ѯ��Χ
     * @access public
     * @param array|string|Closure $scope ��ѯ��Χ����
     * @param array                $args  ����
     * @return $this
     */
    public function scope($scope, ...$args)
    {
        // ��ѯ��Χ�ĵ�һ������ʼ���ǵ�ǰ��ѯ����
        array_unshift($args, $this);

        if ($scope instanceof Closure) {
            call_user_func_array($scope, $args);
            return $this;
        }

        if (is_string($scope)) {
            $scope = explode(',', $scope);
        }

        if ($this->model) {
            // ���ģ����Ĳ�ѯ��Χ����
            foreach ($scope as $name) {
                $method = 'scope' . trim($name);

                if (method_exists($this->model, $method)) {
                    call_user_func_array([$this->model, $method], $args);
                }
            }
        }

        return $this;
    }

    /**
     * ָ�����ݱ�����
     * @access public
     * @param string $pk ����
     * @return $this
     */
    public function pk(string $pk)
    {
        $this->pk = $pk;
        return $this;
    }

    /**
     * ������ڻ���ʱ���ѯ����
     * @access public
     * @param string       $name ʱ����ʽ
     * @param string|array $rule ʱ�䷶Χ
     * @return $this
     */
    public function timeRule(string $name, $rule)
    {
        $this->timeRule[$name] = $rule;
        return $this;
    }

    /**
     * ��ѯ���ڻ���ʱ��
     * @access public
     * @param string       $field �����ֶ���
     * @param string       $op    �Ƚ���������߱��ʽ
     * @param string|array $range �ȽϷ�Χ
     * @param string       $logic AND OR
     * @return $this
     */
    public function whereTime(string $field, string $op, $range = null, string $logic = 'AND')
    {
        if (is_null($range) && isset($this->timeRule[$op])) {
            $range = $this->timeRule[$op];
            $op    = 'between';
        }

        return $this->parseWhereExp($logic, $field, strtolower($op) . ' time', $range, [], true);
    }

    /**
     * ��ѯĳ��ʱ��������
     * @access public
     * @param string $field    �����ֶ���
     * @param string $start    ��ʼʱ��
     * @param string $interval ʱ������λ day/month/year/week/hour/minute/second
     * @param int    $step     ���
     * @param string $logic    AND OR
     * @return $this
     */
    public function whereTimeInterval(string $field, string $start, string $interval = 'day', int $step = 1, string $logic = 'AND')
    {
        $startTime = strtotime($start);
        $endTime   = strtotime(($step > 0 ? '+' : '-') . abs($step) . ' ' . $interval . (abs($step) > 1 ? 's' : ''), $startTime);

        return $this->whereTime($field, 'between', $step > 0 ? [$startTime, $endTime] : [$endTime, $startTime], $logic);
    }

    /**
     * ��ѯ������ whereMonth('time_field', '2018-1')
     * @access public
     * @param string $field �����ֶ���
     * @param string $month �·���Ϣ
     * @param int    $step  ���
     * @param string $logic AND OR
     * @return $this
     */
    public function whereMonth(string $field, string $month = 'this month', int $step = 1, string $logic = 'AND')
    {
        if (in_array($month, ['this month', 'last month'])) {
            $month = date('Y-m', strtotime($month));
        }

        return $this->whereTimeInterval($field, $month, 'month', $step, $logic);
    }

    /**
     * ��ѯ������ whereWeek('time_field', '2018-1-1') ��2018-1-1��ʼ��һ������
     * @access public
     * @param string $field �����ֶ���
     * @param string $week  ����Ϣ
     * @param int    $step  ���
     * @param string $logic AND OR
     * @return $this
     */
    public function whereWeek(string $field, string $week = 'this week', int $step = 1, string $logic = 'AND')
    {
        if (in_array($week, ['this week', 'last week'])) {
            $week = date('Y-m-d', strtotime($week));
        }

        return $this->whereTimeInterval($field, $week, 'week', $step, $logic);
    }

    /**
     * ��ѯ������ whereYear('time_field', '2018')
     * @access public
     * @param string $field �����ֶ���
     * @param string $year  �����Ϣ
     * @param int    $step     ���
     * @param string $logic AND OR
     * @return $this
     */
    public function whereYear(string $field, string $year = 'this year', int $step = 1, string $logic = 'AND')
    {
        if (in_array($year, ['this year', 'last year'])) {
            $year = date('Y', strtotime($year));
        }

        return $this->whereTimeInterval($field, $year . '-1-1', 'year', $step, $logic);
    }

    /**
     * ��ѯ������ whereDay('time_field', '2018-1-1')
     * @access public
     * @param string $field �����ֶ���
     * @param string $day   ������Ϣ
     * @param int    $step     ���
     * @param string $logic AND OR
     * @return $this
     */
    public function whereDay(string $field, string $day = 'today', int $step = 1, string $logic = 'AND')
    {
        if (in_array($day, ['today', 'yesterday'])) {
            $day = date('Y-m-d', strtotime($day));
        }

        return $this->whereTimeInterval($field, $day, 'day', $step, $logic);
    }

    /**
     * ��ѯ���ڻ���ʱ�䷶Χ whereBetweenTime('time_field', '2018-1-1','2018-1-15')
     * @access public
     * @param string     $field     �����ֶ���
     * @param string|int $startTime ��ʼʱ��
     * @param string|int $endTime   ����ʱ��
     * @param string     $logic     AND OR
     * @return $this
     */
    public function whereBetweenTime(string $field, $startTime, $endTime, string $logic = 'AND')
    {
        return $this->whereTime($field, 'between', [$startTime, $endTime], $logic);
    }

    /**
     * ��ѯ���ڻ���ʱ�䷶Χ whereNotBetweenTime('time_field', '2018-1-1','2018-1-15')
     * @access public
     * @param string     $field     �����ֶ���
     * @param string|int $startTime ��ʼʱ��
     * @param string|int $endTime   ����ʱ��
     * @return $this
     */
    public function whereNotBetweenTime(string $field, $startTime, $endTime)
    {
        return $this->whereTime($field, '<', $startTime)
            ->whereTime($field, '>', $endTime);
    }

    /**
     * ��ѯ��ǰʱ��������ʱ���ֶη�Χ whereBetweenTimeField('start_time', 'end_time')
     * @access public
     * @param string $startField ��ʼʱ���ֶ�
     * @param string $endField   ����ʱ���ֶ�
     * @return $this
     */
    public function whereBetweenTimeField(string $startField, string $endField)
    {
        return $this->whereTime($startField, '<=', time())
            ->whereTime($endField, '>=', time());
    }

    /**
     * ��ѯ��ǰʱ�䲻������ʱ���ֶη�Χ whereNotBetweenTimeField('start_time', 'end_time')
     * @access public
     * @param string $startField ��ʼʱ���ֶ�
     * @param string $endField   ����ʱ���ֶ�
     * @return $this
     */
    public function whereNotBetweenTimeField(string $startField, string $endField)
    {
        return $this->whereTime($startField, '>', time())
            ->whereTime($endField, '<', time(), 'OR');
    }

    /**
     * ��ȡ��ǰ���ݱ������
     * @access public
     * @return string|array
     */
    public function getPk()
    {
        if (empty($this->pk)) {
            $this->pk = $this->connection->getPk($this->getTable());
        }

        return $this->pk;
    }

    /**
     * ����������
     * @access public
     * @param array $value �󶨱���ֵ
     * @return $this
     */
    public function bind(array $value)
    {
        $this->bind = array_merge($this->bind, $value);
        return $this;
    }

    /**
     * ����������
     * @access public
     * @param mixed   $value �󶨱���ֵ
     * @param integer $type  ������
     * @param string  $name  �󶨱�ʶ
     * @return string
     */
    public function bindValue($value, int $type = null, string $name = null)
    {
        $name = $name ?: 'ThinkBind_' . (count($this->bind) + 1) . '_';

        $this->bind[$name] = [$value, $type ?: PDO::PARAM_STR];
        return $name;
    }

    /**
     * �������Ƿ��Ѿ���
     * @access public
     * @param string $key ������
     * @return bool
     */
    public function isBind($key)
    {
        return isset($this->bind[$key]);
    }

    /**
     * ������
     * @access public
     * @param string $sql  �󶨵�sql���ʽ
     * @param array  $bind ������
     * @return void
     */
    protected function bindParams(string &$sql, array $bind = []): void
    {
        foreach ($bind as $key => $value) {
            if (is_array($value)) {
                $name = $this->bindValue($value[0], $value[1], $value[2] ?? null);
            } else {
                $name = $this->bindValue($value);
            }

            if (is_numeric($key)) {
                $sql = substr_replace($sql, ':' . $name, strpos($sql, '?'), 1);
            } else {
                $sql = str_replace(':' . $key, ':' . $name, $sql);
            }
        }
    }

    /**
     * ��ѯ����������ֵ
     * @access protected
     * @param array $options ���ʽ����
     * @return $this
     */
    protected function options(array $options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * ��ȡ��ǰ�Ĳ�ѯ����
     * @access public
     * @param string $name ������
     * @return mixed
     */
    public function getOptions(string $name = '')
    {
        if ('' === $name) {
            return $this->options;
        }

        return $this->options[$name] ?? null;
    }

    /**
     * ���õ�ǰ�Ĳ�ѯ����
     * @access public
     * @param string $option ������
     * @param mixed  $value  ����ֵ
     * @return $this
     */
    public function setOption(string $option, $value)
    {
        $this->options[$option] = $value;
        return $this;
    }

    /**
     * ���ù�����ѯ
     * @access public
     * @param array $relation ��������
     * @return $this
     */
    public function relation(array $relation)
    {
        if (!empty($relation)) {
            $this->options['relation'] = $relation;
        }

        return $this;
    }

    /**
     * ���ù�����ѯJOINԤ��ѯ
     * @access public
     * @param array|string $with ������������
     * @return $this
     */
    public function with($with)
    {
        if (!empty($with)) {
            $this->options['with'] = (array) $with;
        }

        return $this;
    }

    /**
     * ����Ԥ���� JOIN��ʽ
     * @access protected
     * @param array|string $with     ����������
     * @param string       $joinType JOIN��ʽ
     * @return $this
     */
    public function withJoin($with, string $joinType = '')
    {
        if (empty($with)) {
            return $this;
        }

        $first = true;

        /** @var Model $class */
        $class = $this->model;
        foreach ((array) $with as $key => $relation) {
            $closure = null;
            $field   = true;

            if ($relation instanceof Closure) {
                // ֧�ֱհ���ѯ���˹�������
                $closure  = $relation;
                $relation = $key;
            } elseif (is_array($relation)) {
                $field    = $relation;
                $relation = $key;
            } elseif (is_string($relation) && strpos($relation, '.')) {
                $relation = strstr($relation, '.', true);
            }

            /** @var Relation $model */
            $relation = App::parseName($relation, 1, false);
            $model    = $class->$relation();

            if ($model instanceof OneToOne) {
                $model->eagerly($this, $relation, $field, $joinType, $closure, $first);
                $first = false;
            } else {
                // ��֧����������
                unset($with[$key]);
            }
        }

        $this->via();

        $this->options['with_join'] = $with;

        return $this;
    }

    /**
     * ���������ֶλ�ȡ��
     * @access public
     * @param string|array $name     �ֶ���
     * @param callable     $callback �հ���ȡ��
     * @return $this
     */
    public function withAttr($name, callable $callback = null)
    {
        if (is_array($name)) {
            $this->options['with_attr'] = $name;
        } else {
            $this->options['with_attr'][$name] = $callback;
        }

        return $this;
    }

    /**
     * ʹ�����������������ֶ�
     * @access public
     * @param array  $fields �����ֶ�
     * @param array  $data   ��������
     * @param string $prefix �ֶ�ǰ׺��ʶ
     * @return $this
     */
    public function withSearch(array $fields, array $data = [], string $prefix = '')
    {
        foreach ($fields as $key => $field) {
            if ($field instanceof Closure) {
                $field($this, $data[$key] ?? null, $data, $prefix);
            } elseif ($this->model) {
                // ���������
                $fieldName = is_numeric($key) ? $field : $key;
                $method    = 'search' . App::parseName($fieldName, 1) . 'Attr';

                if (method_exists($this->model, $method)) {
                    $this->model->$method($this, $data[$field] ?? null, $data, $prefix);
                }
            }
        }

        return $this;
    }

    /**
     * ����ͳ��
     * @access protected
     * @param array|string $relations ����������
     * @param string       $aggregate �ۺϲ�ѯ����
     * @param string       $field     �ֶ�
     * @param bool         $subQuery  �Ƿ�ʹ���Ӳ�ѯ
     * @return $this
     */
    protected function withAggregate($relations, string $aggregate = 'count', $field = '*', bool $subQuery = true)
    {
        if (!$subQuery) {
            $this->options['with_count'][] = [$relations, $aggregate, $field];
        } else {
            if (!isset($this->options['field'])) {
                $this->field('*');
            }

            foreach ((array) $relations as $key => $relation) {
                $closure = $aggregateField = null;

                if ($relation instanceof Closure) {
                    $closure  = $relation;
                    $relation = $key;
                } elseif (!is_int($key)) {
                    $aggregateField = $relation;
                    $relation       = $key;
                }

                $relation = App::parseName($relation, 1, false);

                $count = $this->model
                    ->$relation()
                    ->getRelationCountQuery($closure, $aggregate, $field, $aggregateField);

                if (empty($aggregateField)) {
                    $aggregateField = App::parseName($relation) . '_' . $aggregate;
                }

                $this->field(['(' . $count . ')' => $aggregateField]);
            }
        }

        return $this;
    }

    /**
     * ����ͳ��
     * @access public
     * @param string|array $relation ����������
     * @param bool         $subQuery �Ƿ�ʹ���Ӳ�ѯ
     * @return $this
     */
    public function withCount($relation, bool $subQuery = true)
    {
        return $this->withAggregate($relation, 'count', '*', $subQuery);
    }

    /**
     * ����ͳ��Sum
     * @access public
     * @param string|array $relation ����������
     * @param string       $field    �ֶ�
     * @param bool         $subQuery �Ƿ�ʹ���Ӳ�ѯ
     * @return $this
     */
    public function withSum($relation, string $field, bool $subQuery = true)
    {
        return $this->withAggregate($relation, 'sum', $field, $subQuery);
    }

    /**
     * ����ͳ��Max
     * @access public
     * @param string|array $relation ����������
     * @param string       $field    �ֶ�
     * @param bool         $subQuery �Ƿ�ʹ���Ӳ�ѯ
     * @return $this
     */
    public function withMax($relation, string $field, bool $subQuery = true)
    {
        return $this->withAggregate($relation, 'max', $field, $subQuery);
    }

    /**
     * ����ͳ��Min
     * @access public
     * @param string|array $relation ����������
     * @param string       $field    �ֶ�
     * @param bool         $subQuery �Ƿ�ʹ���Ӳ�ѯ
     * @return $this
     */
    public function withMin($relation, string $field, bool $subQuery = true)
    {
        return $this->withAggregate($relation, 'min', $field, $subQuery);
    }

    /**
     * ����ͳ��Avg
     * @access public
     * @param string|array $relation ����������
     * @param string       $field    �ֶ�
     * @param bool         $subQuery �Ƿ�ʹ���Ӳ�ѯ
     * @return $this
     */
    public function withAvg($relation, string $field, bool $subQuery = true)
    {
        return $this->withAggregate($relation, 'avg', $field, $subQuery);
    }

    /**
     * ���õ�ǰ�ֶ���ӵı����
     * @access public
     * @param string $via ��ʱ�����
     * @return $this
     */
    public function via(string $via = '')
    {
        $this->options['via'] = $via;

        return $this;
    }

    /**
     * �����¼ �Զ��ж�insert����update
     * @access public
     * @param array $data        ����
     * @param bool  $forceInsert �Ƿ�ǿ��insert
     * @return integer
     */
    public function save(array $data = [], bool $forceInsert = false)
    {
        if ($forceInsert) {
            return $this->insert($data);
        }

        $this->options['data'] = array_merge($this->options['data'] ?? [], $data);

        if (!empty($this->options['where'])) {
            $isUpdate = true;
        } else {
            $isUpdate = $this->parseUpdateData($this->options['data']);
        }

        return $isUpdate ? $this->update() : $this->insert();
    }

    /**
     * �����¼
     * @access public
     * @param array   $data         ����
     * @param boolean $getLastInsID ������������
     * @return integer|string
     */
    public function insert(array $data = [], bool $getLastInsID = false)
    {
        if (!empty($data)) {
            $this->options['data'] = $data;
        }

        return $this->connection->insert($this, $getLastInsID);
    }

    /**
     * �����¼����ȡ����ID
     * @access public
     * @param array $data ����
     * @return integer|string
     */
    public function insertGetId(array $data)
    {
        return $this->insert($data, true);
    }

    /**
     * ���������¼
     * @access public
     * @param array   $dataSet ���ݼ�
     * @param integer $limit   ÿ��д����������
     * @return integer
     */
    public function insertAll(array $dataSet = [], int $limit = 0): int
    {
        if (empty($dataSet)) {
            $dataSet = $this->options['data'] ?? [];
        }

        if (empty($limit) && !empty($this->options['limit']) && is_numeric($this->options['limit'])) {
            $limit = (int) $this->options['limit'];
        }

        return $this->connection->insertAll($this, $dataSet, $limit);
    }

    /**
     * ͨ��Select��ʽ�����¼
     * @access public
     * @param array  $fields Ҫ��������ݱ��ֶ���
     * @param string $table  Ҫ��������ݱ���
     * @return integer
     * @throws PDOException
     */
    public function selectInsert(array $fields, string $table): int
    {
        return $this->connection->selectInsert($this, $fields, $table);
    }

    /**
     * ���¼�¼
     * @access public
     * @param mixed $data ����
     * @return integer
     * @throws Exception
     * @throws PDOException
     */
    public function update(array $data = []): int
    {
        if (!empty($data)) {
            $this->options['data'] = array_merge($this->options['data'] ?? [], $data);
        }

        if (empty($this->options['where'])) {
            $this->parseUpdateData($this->options['data']);
        }

        if (empty($this->options['where']) && $this->model) {
            $this->where($this->model->getWhere());
        }

        if (empty($this->options['where'])) {
            // ���û���κθ���������ִ��
            throw new Exception('miss update condition');
        }

        return $this->connection->update($this);
    }

    /**
     * ɾ����¼
     * @access public
     * @param mixed $data ���ʽ true ��ʾǿ��ɾ��
     * @return int
     * @throws Exception
     * @throws PDOException
     */
    public function delete($data = null): int
    {
        if (!is_null($data) && true !== $data) {
            // ARģʽ������������
            $this->parsePkWhere($data);
        }

        if (empty($this->options['where']) && $this->model) {
            $this->where($this->model->getWhere());
        }

        if (true !== $data && empty($this->options['where'])) {
            // �������Ϊ�� ������ɾ������ �������� 1=1
            throw new Exception('delete without condition');
        }

        if (!empty($this->options['soft_delete'])) {
            // ��ɾ��
            list($field, $condition) = $this->options['soft_delete'];
            if ($condition) {
                unset($this->options['soft_delete']);
                $this->options['data'] = [$field => $condition];

                return $this->connection->update($this);
            }
        }

        $this->options['data'] = $data;

        return $this->connection->delete($this);
    }

    /**
     * ִ�в�ѯ��ֻ����PDOStatement����
     * @access public
     * @return PDOStatement
     */
    public function getPdo(): PDOStatement
    {
        return $this->connection->pdo($this);
    }

    /**
     * ʹ���α���Ҽ�¼
     * @access public
     * @param mixed $data ����
     * @return \Generator
     */
    public function cursor($data = null)
    {
        if (!is_null($data)) {
            // ������������
            $this->parsePkWhere($data);
        }

        $this->options['data'] = $data;

        $connection = clone $this->connection;

        return $connection->cursor($this);
    }

    /**
     * ���Ҽ�¼
     * @access public
     * @param mixed $data ����
     * @return Collection|ModelCollection
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function select($data = null): Collection
    {
        if (!is_null($data)) {
            // ������������
            $this->parsePkWhere($data);
        }

        $resultSet = $this->connection->select($this);

        // ���ؽ������
        if (!empty($this->options['fail']) && count($resultSet) == 0) {
            $this->throwNotFound();
        }

        // �����б��ȡ��Ĵ���
        if (!empty($this->model)) {
            // ����ģ�Ͷ���
            $resultSet = $this->resultSetToModelCollection($resultSet);
        } else {
            $this->resultSet($resultSet);
        }

        return $resultSet;
    }

    /**
     * ��ѯ����ת��Ϊģ�����ݼ�����
     * @access protected
     * @param array $resultSet ���ݼ�
     * @return ModelCollection
     */
    protected function resultSetToModelCollection(array $resultSet): ModelCollection
    {
        if (!empty($this->options['collection']) && is_string($this->options['collection'])) {
            $collection = $this->options['collection'];
        }

        if (empty($resultSet)) {
            return $this->model->toCollection([], $collection ?? null);
        }

        // ��鶯̬��ȡ��
        if (!empty($this->options['with_attr'])) {
            foreach ($this->options['with_attr'] as $name => $val) {
                if (strpos($name, '.')) {
                    list($relation, $field) = explode('.', $name);

                    $withRelationAttr[$relation][$field] = $val;
                    unset($this->options['with_attr'][$name]);
                }
            }
        }

        $withRelationAttr = $withRelationAttr ?? [];

        foreach ($resultSet as $key => &$result) {
            // ����ת��Ϊģ�Ͷ���
            $this->resultToModel($result, $this->options, true, $withRelationAttr);
        }

        if (!empty($this->options['with'])) {
            // Ԥ����
            $result->eagerlyResultSet($resultSet, $this->options['with'], $withRelationAttr);
        }

        if (!empty($this->options['with_join'])) {
            // Ԥ����
            $result->eagerlyResultSet($resultSet, $this->options['with_join'], $withRelationAttr, true);
        }

        // ģ�����ݼ�ת��
        return $this->model->toCollection($resultSet, $collection ?? null);
    }

    /**
     * �������ݼ�
     * @access public
     * @param array $resultSet ���ݼ�
     * @return void
     */
    protected function resultSet(array &$resultSet): void
    {
        if (!empty($this->options['json'])) {
            foreach ($resultSet as &$result) {
                $this->jsonResult($result, $this->options['json'], true);
            }
        }

        if (!empty($this->options['with_attr'])) {
            foreach ($resultSet as &$result) {
                $this->getResultAttr($result, $this->options['with_attr']);
            }
        }

        if (!empty($this->options['visible']) || !empty($this->options['hidden'])) {
            foreach ($resultSet as &$result) {
                $this->filterResult($result);
            }
        }

        // ����Collection����
        $resultSet = new Collection($resultSet);
    }

    /**
     * ���ҵ�����¼
     * @access public
     * @param mixed $data ��ѯ����
     * @return array|Model|null
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function find($data = null)
    {
        if (!is_null($data)) {
            // ARģʽ������������
            $this->parsePkWhere($data);
        }

        $result = $this->connection->find($this);

        // ���ݴ���
        if (empty($result)) {
            return $this->resultToEmpty();
        }

        if (!empty($this->model)) {
            // ����ģ�Ͷ���
            $this->resultToModel($result, $this->options);
        } else {
            $this->result($result);
        }

        return $result;
    }

    /**
     * ���ҵ�����¼ �����ڷ��ؿ����ݣ����߿�ģ�ͣ�
     * @access public
     * @param mixed $data ����
     * @return array|Model
     */
    public function findOrEmpty($data = null)
    {
        return $this->allowEmpty(true)->find($data);
    }

    /**
     * ���������
     * @access protected
     * @return array|Model|null
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    protected function resultToEmpty()
    {
        if (!empty($this->options['fail'])) {
            $this->throwNotFound();
        } elseif (!empty($this->options['allow_empty'])) {
            return !empty($this->model) ? $this->model->newInstance()->setQuery($this) : [];
        }
    }

    /**
     * ��ȡģ�͵ĸ�������
     * @access protected
     * @param array $options ��ѯ����
     */
    protected function getModelUpdateCondition(array $options)
    {
        return $options['where']['AND'] ?? null;
    }

    /**
     * ��������
     * @access protected
     * @param array $result ��ѯ����
     * @return void
     */
    protected function result(array &$result): void
    {
        if (!empty($this->options['json'])) {
            $this->jsonResult($result, $this->options['json'], true);
        }

        if (!empty($this->options['with_attr'])) {
            $this->getResultAttr($result, $this->options['with_attr']);
        }

        $this->filterResult($result);
    }

    /**
     * �������ݵĿɼ�������
     * @access protected
     * @param array $result ��ѯ����
     * @return void
     */
    protected function filterResult(&$result): void
    {
        if (!empty($this->options['visible'])) {
            foreach ($this->options['visible'] as $key) {
                $array[] = $key;
            }
            $result = array_intersect_key($result, array_flip($array));
        } elseif (!empty($this->options['hidden'])) {
            foreach ($this->options['hidden'] as $key) {
                $array[] = $key;
            }
            $result = array_diff_key($result, array_flip($array));
        }
    }

    /**
     * ʹ�û�ȡ����������
     * @access protected
     * @param array $result   ��ѯ����
     * @param array $withAttr �ֶλ�ȡ��
     * @return void
     */
    protected function getResultAttr(array &$result, array $withAttr = []): void
    {
        foreach ($withAttr as $name => $closure) {
            $name = App::parseName($name);

            if (strpos($name, '.')) {
                // ֧��JSON�ֶ� ��ȡ������
                list($key, $field) = explode('.', $name);

                if (isset($result[$key])) {
                    $result[$key][$field] = $closure($result[$key][$field] ?? null, $result[$key]);
                }
            } else {
                $result[$name] = $closure($result[$name] ?? null, $result);
            }
        }
    }

    /**
     * JSON�ֶ�����ת��
     * @access protected
     * @param array $result           ��ѯ����
     * @param array $json             JSON�ֶ�
     * @param bool  $assoc            �Ƿ�ת��Ϊ����
     * @param array $withRelationAttr ������ȡ��
     * @return void
     */
    protected function jsonResult(array &$result, array $json = [], bool $assoc = false, array $withRelationAttr = []): void
    {
        foreach ($json as $name) {
            if (!isset($result[$name])) {
                continue;
            }

            $result[$name] = json_decode($result[$name], true);

            if (isset($withRelationAttr[$name])) {
                foreach ($withRelationAttr[$name] as $key => $closure) {
                    $result[$name][$key] = $closure($result[$name][$key] ?? null, $result[$name]);
                }
            }

            if (!$assoc) {
                $result[$name] = (object) $result[$name];
            }
        }
    }

    /**
     * ��ѯ����ת��Ϊģ�Ͷ���
     * @access protected
     * @param array $result           ��ѯ����
     * @param array $options          ��ѯ����
     * @param bool  $resultSet        �Ƿ�Ϊ���ݼ���ѯ
     * @param array $withRelationAttr �����ֶλ�ȡ��
     * @return void
     */
    protected function resultToModel(array &$result, array $options = [], bool $resultSet = false, array $withRelationAttr = []): void
    {
        // ��̬��ȡ��
        if (!empty($options['with_attr']) && empty($withRelationAttr)) {
            foreach ($options['with_attr'] as $name => $val) {
                if (strpos($name, '.')) {
                    list($relation, $field) = explode('.', $name);

                    $withRelationAttr[$relation][$field] = $val;
                    unset($options['with_attr'][$name]);
                }
            }
        }

        // JSON ���ݴ���
        if (!empty($options['json'])) {
            $this->jsonResult($result, $options['json'], $options['json_assoc'], $withRelationAttr);
        }

        $result = $this->model
            ->newInstance($result, $resultSet ? null : $this->getModelUpdateCondition($options))
            ->setQuery($this);

        // ��̬��ȡ��
        if (!empty($options['with_attr'])) {
            $result->withAttribute($options['with_attr']);
        }

        // ������Կ���
        if (!empty($options['visible'])) {
            $result->visible($options['visible']);
        } elseif (!empty($options['hidden'])) {
            $result->hidden($options['hidden']);
        }

        if (!empty($options['append'])) {
            $result->append($options['append']);
        }

        // ������ѯ
        if (!empty($options['relation'])) {
            $result->relationQuery($options['relation'], $withRelationAttr);
        }

        // Ԥ�����ѯ
        if (!$resultSet && !empty($options['with'])) {
            $result->eagerlyResult($result, $options['with'], $withRelationAttr);
        }

        // JOINԤ�����ѯ
        if (!$resultSet && !empty($options['with_join'])) {
            $result->eagerlyResult($result, $options['with_join'], $withRelationAttr, true);
        }

        // ����ͳ��
        if (!empty($options['with_count'])) {
            foreach ($options['with_count'] as $val) {
                $result->relationCount($result, (array) $val[0], $val[1], $val[2]);
            }
        }
    }

    /**
     * ��ѯʧ�� �׳��쳣
     * @access protected
     * @return void
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    protected function throwNotFound(): void
    {
        if (!empty($this->model)) {
            $class = get_class($this->model);
            throw new ModelNotFoundException('model data Not Found:' . $class, $class, $this->options);
        }

        $table = $this->getTable();
        throw new DataNotFoundException('table data not Found:' . $table, $table, $this->options);
    }

    /**
     * ���Ҷ�����¼ ������������׳��쳣
     * @access public
     * @param array|string|Query|Closure $data ����
     * @return array|Model
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function selectOrFail($data = null)
    {
        return $this->failException(true)->select($data);
    }

    /**
     * ���ҵ�����¼ ������������׳��쳣
     * @access public
     * @param array|string|Query|Closure $data ����
     * @return array|Model
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function findOrFail($data = null)
    {
        return $this->failException(true)->find($data);
    }

    /**
     * �������ݷ��ش���
     * @access public
     * @param integer      $count    ÿ�δ������������
     * @param callable     $callback ����ص�����
     * @param string|array $column   ����������ֶ���
     * @param string       $order    �ֶ�����
     * @return bool
     * @throws DbException
     */
    public function chunk(int $count, callable $callback, $column = null, string $order = 'asc'): bool
    {
        $options = $this->getOptions();
        $column  = $column ?: $this->getPk();

        if (isset($options['order'])) {
            unset($options['order']);
        }

        $bind = $this->bind;

        if (is_array($column)) {
            $times = 1;
            $query = $this->options($options)->page($times, $count);
        } else {
            $query = $this->options($options)->limit($count);

            if (strpos($column, '.')) {
                list($alias, $key) = explode('.', $column);
            } else {
                $key = $column;
            }
        }

        $resultSet = $query->order($column, $order)->select();

        while (count($resultSet) > 0) {
            if (false === call_user_func($callback, $resultSet)) {
                return false;
            }

            if (isset($times)) {
                $times++;
                $query = $this->options($options)->page($times, $count);
            } else {
                $end    = end($resultSet);
                $lastId = is_array($end) ? $end[$key] : $end->getData($key);

                $query = $this->options($options)
                    ->limit($count)
                    ->where($column, 'asc' == strtolower($order) ? '>' : '<', $lastId);
            }

            $resultSet = $query->bind($bind)->order($column, $order)->select();
        }

        return true;
    }

    /**
     * ��ȡ�󶨵Ĳ��� �����
     * @access public
     * @param bool $clear �Ƿ���հ�����
     * @return array
     */
    public function getBind(bool $clear = true): array
    {
        $bind = $this->bind;
        if ($clear) {
            $this->bind = [];
        }

        return $bind;
    }

    /**
     * �����Ӳ�ѯSQL
     * @access public
     * @param bool $sub �Ƿ��������
     * @return string
     * @throws DbException
     */
    public function buildSql(bool $sub = true): string
    {
        return $sub ? '( ' . $this->fetchSql()->select() . ' )' : $this->fetchSql()->select();
    }

    /**
     * ��ͼ��ѯ����
     * @access protected
     * @param array $options ��ѯ����
     * @return void
     */
    protected function parseView(array &$options): void
    {
        foreach (['AND', 'OR'] as $logic) {
            if (isset($options['where'][$logic])) {
                foreach ($options['where'][$logic] as $key => $val) {
                    if (array_key_exists($key, $options['map'])) {
                        array_shift($val);
                        array_unshift($val, $options['map'][$key]);
                        $options['where'][$logic][$options['map'][$key]] = $val;
                        unset($options['where'][$logic][$key]);
                    }
                }
            }
        }

        if (isset($options['order'])) {
            // ��ͼ��ѯ������
            foreach ($options['order'] as $key => $val) {
                if (is_numeric($key) && is_string($val)) {
                    if (strpos($val, ' ')) {
                        list($field, $sort) = explode(' ', $val);
                        if (array_key_exists($field, $options['map'])) {
                            $options['order'][$options['map'][$field]] = $sort;
                            unset($options['order'][$key]);
                        }
                    } elseif (array_key_exists($val, $options['map'])) {
                        $options['order'][$options['map'][$val]] = 'asc';
                        unset($options['order'][$key]);
                    }
                } elseif (array_key_exists($key, $options['map'])) {
                    $options['order'][$options['map'][$key]] = $val;
                    unset($options['order'][$key]);
                }
            }
        }
    }

    /**
     * ���������Ƿ���ڸ�������
     * @access public
     * @param array $data ����
     * @return bool
     * @throws Exception
     */
    public function parseUpdateData(&$data): bool
    {
        $pk       = $this->getPk();
        $isUpdate = false;
        // ��������������� ���Զ���Ϊ��������
        if (is_string($pk) && isset($data[$pk])) {
            $this->where($pk, '=', $data[$pk]);
            $this->options['key'] = $data[$pk];
            unset($data[$pk]);
            $isUpdate = true;
        } elseif (is_array($pk)) {
            foreach ($pk as $field) {
                if (isset($data[$field])) {
                    $this->where($field, '=', $data[$field]);
                    $isUpdate = true;
                } else {
                    // ���ȱ�ٸ�������������ִ��
                    throw new Exception('miss complex primary data');
                }
                unset($data[$field]);
            }
        }

        return $isUpdate;
    }

    /**
     * ������ֵת��Ϊ��ѯ���� ֧�ָ�������
     * @access public
     * @param array|string $data ��������
     * @return void
     * @throws Exception
     */
    public function parsePkWhere($data): void
    {
        $pk = $this->getPk();

        if (is_string($pk)) {
            // ��ȡ���ݱ�
            if (empty($this->options['table'])) {
                $this->options['table'] = $this->getTable();
            }

            $table = is_array($this->options['table']) ? key($this->options['table']) : $this->options['table'];

            if (!empty($this->options['alias'][$table])) {
                $alias = $this->options['alias'][$table];
            }

            $key = isset($alias) ? $alias . '.' . $pk : $pk;
            // ����������ѯ
            if (is_array($data)) {
                $this->where($key, 'in', $data);
            } else {
                $this->where($key, '=', $data);
                $this->options['key'] = $data;
            }
        }
    }

    /**
     * �������ʽ�������ڲ�ѯ����д�������
     * @access public
     * @return array
     */
    public function parseOptions(): array
    {
        $options = $this->getOptions();

        // ��ȡ���ݱ�
        if (empty($options['table'])) {
            $options['table'] = $this->getTable();
        }

        if (!isset($options['where'])) {
            $options['where'] = [];
        } elseif (isset($options['view'])) {
            // ��ͼ��ѯ��������
            $this->parseView($options);
        }

        if (!isset($options['field'])) {
            $options['field'] = '*';
        }

        foreach (['data', 'order', 'join', 'union'] as $name) {
            if (!isset($options[$name])) {
                $options[$name] = [];
            }
        }

        if (!isset($options['strict'])) {
            $options['strict'] = $this->connection->getConfig('fields_strict');
        }

        foreach (['master', 'lock', 'fetch_sql', 'array', 'distinct', 'procedure'] as $name) {
            if (!isset($options[$name])) {
                $options[$name] = false;
            }
        }

        foreach (['group', 'having', 'limit', 'force', 'comment', 'partition', 'duplicate', 'extra'] as $name) {
            if (!isset($options[$name])) {
                $options[$name] = '';
            }
        }

        if (isset($options['page'])) {
            // ����ҳ������limit
            list($page, $listRows) = $options['page'];
            $page                  = $page > 0 ? $page : 1;
            $listRows              = $listRows ?: (is_numeric($options['limit']) ? $options['limit'] : 20);
            $offset                = $listRows * ($page - 1);
            $options['limit']      = $offset . ',' . $listRows;
        }

        $this->options = $options;

        return $options;
    }

    public function __debugInfo()
    {
        return [
            'name'    => $this->name,
            'pk'      => $this->pk,
            'prefix'  => $this->prefix,
            'bind'    => $this->bind,
            'options' => $this->options,
        ];
    }
}
