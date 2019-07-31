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
use think\Exception;

/**
 * Db Builder
 */
abstract class Builder
{
    /**
     * Connection����
     * @var Connection
     */
    protected $connection;

    /**
     * ��ѯ���ʽӳ��
     * @var array
     */
    protected $exp = ['NOTLIKE' => 'NOT LIKE', 'NOTIN' => 'NOT IN', 'NOTBETWEEN' => 'NOT BETWEEN', 'NOTEXISTS' => 'NOT EXISTS', 'NOTNULL' => 'NOT NULL', 'NOTBETWEEN TIME' => 'NOT BETWEEN TIME'];

    /**
     * ��ѯ���ʽ����
     * @var array
     */
    protected $parser = [
        'parseCompare'     => ['=', '<>', '>', '>=', '<', '<='],
        'parseLike'        => ['LIKE', 'NOT LIKE'],
        'parseBetween'     => ['NOT BETWEEN', 'BETWEEN'],
        'parseIn'          => ['NOT IN', 'IN'],
        'parseExp'         => ['EXP'],
        'parseNull'        => ['NOT NULL', 'NULL'],
        'parseBetweenTime' => ['BETWEEN TIME', 'NOT BETWEEN TIME'],
        'parseTime'        => ['< TIME', '> TIME', '<= TIME', '>= TIME'],
        'parseExists'      => ['NOT EXISTS', 'EXISTS'],
        'parseColumn'      => ['COLUMN'],
    ];

    /**
     * SELECT SQL���ʽ
     * @var string
     */
    protected $selectSql = 'SELECT%DISTINCT%%EXTRA% %FIELD% FROM %TABLE%%FORCE%%JOIN%%WHERE%%GROUP%%HAVING%%UNION%%ORDER%%LIMIT% %LOCK%%COMMENT%';

    /**
     * INSERT SQL���ʽ
     * @var string
     */
    protected $insertSql = '%INSERT%%EXTRA% INTO %TABLE% (%FIELD%) VALUES (%DATA%) %COMMENT%';

    /**
     * INSERT ALL SQL���ʽ
     * @var string
     */
    protected $insertAllSql = '%INSERT%%EXTRA% INTO %TABLE% (%FIELD%) %DATA% %COMMENT%';

    /**
     * UPDATE SQL���ʽ
     * @var string
     */
    protected $updateSql = 'UPDATE%EXTRA% %TABLE% SET %SET%%JOIN%%WHERE%%ORDER%%LIMIT% %LOCK%%COMMENT%';

    /**
     * DELETE SQL���ʽ
     * @var string
     */
    protected $deleteSql = 'DELETE%EXTRA% FROM %TABLE%%USING%%JOIN%%WHERE%%ORDER%%LIMIT% %LOCK%%COMMENT%';

    /**
     * �ܹ�����
     * @access public
     * @param  Connection    $connection ���ݿ����Ӷ���ʵ��
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * ��ȡ��ǰ�����Ӷ���ʵ��
     * @access public
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * ע���ѯ���ʽ����
     * @access public
     * @param  string    $name   ��������
     * @param  array     $parser ƥ����ʽ����
     * @return $this
     */
    public function bindParser(string $name, array $parser)
    {
        $this->parser[$name] = $parser;
        return $this;
    }

    /**
     * ���ݷ���
     * @access protected
     * @param  Query     $query     ��ѯ����
     * @param  array     $data      ����
     * @param  array     $fields    �ֶ���Ϣ
     * @param  array     $bind      ������
     * @return array
     */
    protected function parseData(Query $query, array $data = [], array $fields = [], array $bind = []): array
    {
        if (empty($data)) {
            return [];
        }

        $options = $query->getOptions();

        // ��ȡ����Ϣ
        if (empty($bind)) {
            $bind = $query->getFieldsBindType();
        }

        if (empty($fields)) {
            if ('*' == $options['field']) {
                $fields = array_keys($bind);
            } else {
                $fields = $options['field'];
            }
        }

        $result = [];

        foreach ($data as $key => $val) {
            $item = $this->parseKey($query, $key, true);

            if ($val instanceof Raw) {
                $result[$item] = $val->getValue();
                continue;
            } elseif (!is_scalar($val) && (in_array($key, (array) $query->getOptions('json')) || 'json' == $query->getFieldType($key))) {
                $val = json_encode($val);
            }

            if (false !== strpos($key, '->')) {
                list($key, $name) = explode('->', $key, 2);
                $item             = $this->parseKey($query, $key);
                $result[$item]    = 'json_set(' . $item . ', \'$.' . $name . '\', ' . $this->parseDataBind($query, $key . '->' . $name, $val, $bind) . ')';
            } elseif (false === strpos($key, '.') && !in_array($key, $fields, true)) {
                if ($options['strict']) {
                    throw new Exception('fields not exists:[' . $key . ']');
                }
            } elseif (is_null($val)) {
                $result[$item] = 'NULL';
            } elseif (is_array($val) && !empty($val)) {
                switch (strtoupper($val[0])) {
                    case 'INC':
                        $result[$item] = $item . ' + ' . floatval($val[1]);
                        break;
                    case 'DEC':
                        $result[$item] = $item . ' - ' . floatval($val[1]);
                        break;
                }
            } elseif (is_scalar($val)) {
                // ���˷Ǳ�������
                $result[$item] = $this->parseDataBind($query, $key, $val, $bind);
            }
        }

        return $result;
    }

    /**
     * ���ݰ󶨴���
     * @access protected
     * @param  Query     $query     ��ѯ����
     * @param  string    $key       �ֶ���
     * @param  mixed     $data      ����
     * @param  array     $bind      ������
     * @return string
     */
    protected function parseDataBind(Query $query, string $key, $data, array $bind = []): string
    {
        if ($data instanceof Raw) {
            return $data->getValue();
        }

        $name = $query->bindValue($data, $bind[$key] ?? PDO::PARAM_STR);

        return ':' . $name;
    }

    /**
     * �ֶ�������
     * @access public
     * @param  Query  $query    ��ѯ����
     * @param  mixed  $key      �ֶ���
     * @param  bool   $strict   �ϸ���
     * @return string
     */
    public function parseKey(Query $query, $key, bool $strict = false): string
    {
        return $key;
    }

    /**
     * ��ѯ�����������
     * @access protected
     * @param  Query  $query    ��ѯ����
     * @param  string $extra    �������
     * @return string
     */
    protected function parseExtra(Query $query, string $extra): string
    {
        return preg_match('/^[\w]+$/i', $extra) ? ' ' . strtoupper($extra) : '';
    }

    /**
     * field����
     * @access protected
     * @param  Query     $query     ��ѯ����
     * @param  mixed     $fields    �ֶ���
     * @return string
     */
    protected function parseField(Query $query, $fields): string
    {
        if (is_array($fields)) {
            // ֧�� 'field1'=>'field2' �������ֶα�������
            $array = [];

            foreach ($fields as $key => $field) {
                if ($field instanceof Raw) {
                    $array[] = $field->getValue();
                } elseif (!is_numeric($key)) {
                    $array[] = $this->parseKey($query, $key) . ' AS ' . $this->parseKey($query, $field, true);
                } else {
                    $array[] = $this->parseKey($query, $field);
                }
            }

            $fieldsStr = implode(',', $array);
        } else {
            $fieldsStr = '*';
        }

        return $fieldsStr;
    }

    /**
     * table����
     * @access protected
     * @param  Query     $query     ��ѯ����
     * @param  mixed     $tables    ����
     * @return string
     */
    protected function parseTable(Query $query, $tables): string
    {
        $item    = [];
        $options = $query->getOptions();

        foreach ((array) $tables as $key => $table) {
            if ($table instanceof Raw) {
                $item[] = $table->getValue();
            } elseif (!is_numeric($key)) {
                $item[] = $this->parseKey($query, $key) . ' ' . $this->parseKey($query, $table);
            } elseif (isset($options['alias'][$table])) {
                $item[] = $this->parseKey($query, $table) . ' ' . $this->parseKey($query, $options['alias'][$table]);
            } else {
                $item[] = $this->parseKey($query, $table);
            }
        }

        return implode(',', $item);
    }

    /**
     * where����
     * @access protected
     * @param  Query     $query   ��ѯ����
     * @param  mixed     $where   ��ѯ����
     * @return string
     */
    protected function parseWhere(Query $query, array $where): string
    {
        $options  = $query->getOptions();
        $whereStr = $this->buildWhere($query, $where);

        if (!empty($options['soft_delete'])) {
            // ������ɾ������
            list($field, $condition) = $options['soft_delete'];

            $binds    = $query->getFieldsBindType();
            $whereStr = $whereStr ? '( ' . $whereStr . ' ) AND ' : '';
            $whereStr = $whereStr . $this->parseWhereItem($query, $field, $condition, $binds);
        }

        return empty($whereStr) ? '' : ' WHERE ' . $whereStr;
    }

    /**
     * ���ɲ�ѯ����SQL
     * @access public
     * @param  Query     $query     ��ѯ����
     * @param  mixed     $where     ��ѯ����
     * @return string
     */
    public function buildWhere(Query $query, array $where): string
    {
        if (empty($where)) {
            $where = [];
        }

        $whereStr = '';

        $binds = $query->getFieldsBindType();

        foreach ($where as $logic => $val) {
            $str = $this->parseWhereLogic($query, $logic, $val, $binds);

            $whereStr .= empty($whereStr) ? substr(implode(' ', $str), strlen($logic) + 1) : implode(' ', $str);
        }

        return $whereStr;
    }

    /**
     * ��ͬ�ֶ�ʹ����ͬ��ѯ������AND��
     * @access protected
     * @param  Query  $query ��ѯ����
     * @param  string $logic Logic
     * @param  array  $val   ��ѯ����
     * @param  array  $binds ������
     * @return array
     */
    protected function parseWhereLogic(Query $query, string $logic, array $val, array $binds = []): array
    {
        $where = [];
        foreach ($val as $value) {
            if ($value instanceof Raw) {
                $where[] = ' ' . $logic . ' ( ' . $value->getValue() . ' )';
                continue;
            }

            if (is_array($value)) {
                if (key($value) !== 0) {
                    throw new Exception('where express error:' . var_export($value, true));
                }
                $field = array_shift($value);
            } elseif (!($value instanceof Closure)) {
                throw new Exception('where express error:' . var_export($value, true));
            }

            if ($value instanceof Closure) {
                // ʹ�ñհ���ѯ
                $where[] = $this->parseClousreWhere($query, $value, $logic);
            } elseif (is_array($field)) {
                $where[] = $this->parseMultiWhereField($query, $value, $field, $logic, $binds);
            } elseif ($field instanceof Raw) {
                $where[] = ' ' . $logic . ' ' . $this->parseWhereItem($query, $field, $value, $binds);
            } elseif (strpos($field, '|')) {
                $where[] = $this->parseFieldsOr($query, $value, $field, $logic, $binds);
            } elseif (strpos($field, '&')) {
                $where[] = $this->parseFieldsAnd($query, $value, $field, $logic, $binds);
            } else {
                // ���ֶ�ʹ�ñ��ʽ��ѯ
                $field   = is_string($field) ? $field : '';
                $where[] = ' ' . $logic . ' ' . $this->parseWhereItem($query, $field, $value, $binds);
            }
        }

        return $where;
    }

    /**
     * ��ͬ�ֶ�ʹ����ͬ��ѯ������AND��
     * @access protected
     * @param  Query  $query ��ѯ����
     * @param  mixed  $value ��ѯ����
     * @param  string $field ��ѯ�ֶ�
     * @param  string $logic Logic
     * @param  array  $binds ������
     * @return string
     */
    protected function parseFieldsAnd(Query $query, $value, string $field, string $logic, array $binds): string
    {
        $item = [];

        foreach (explode('&', $field) as $k) {
            $item[] = $this->parseWhereItem($query, $k, $value, $binds);
        }

        return ' ' . $logic . ' ( ' . implode(' AND ', $item) . ' )';
    }

    /**
     * ��ͬ�ֶ�ʹ����ͬ��ѯ������OR��
     * @access protected
     * @param  Query  $query ��ѯ����
     * @param  mixed  $value ��ѯ����
     * @param  string $field ��ѯ�ֶ�
     * @param  string $logic Logic
     * @param  array  $binds ������
     * @return string
     */
    protected function parseFieldsOr(Query $query, $value, string $field, string $logic, array $binds): string
    {
        $item = [];

        foreach (explode('|', $field) as $k) {
            $item[] = $this->parseWhereItem($query, $k, $value, $binds);
        }

        return ' ' . $logic . ' ( ' . implode(' OR ', $item) . ' )';
    }

    /**
     * �հ���ѯ
     * @access protected
     * @param  Query   $query ��ѯ����
     * @param  Closure $value ��ѯ����
     * @param  string  $logic Logic
     * @return string
     */
    protected function parseClousreWhere(Query $query, Closure $value, string $logic): string
    {
        $newQuery = $query->newQuery()->setConnection($this->connection);
        $value($newQuery);
        $whereClause = $this->buildWhere($query, $newQuery->getOptions('where') ?: []);

        if (!empty($whereClause)) {
            $where = ' ' . $logic . ' ( ' . $whereClause . ' )';
        }

        return $where ?? '';
    }

    /**
     * ����������ѯ
     * @access protected
     * @param  Query  $query ��ѯ����
     * @param  mixed  $value ��ѯ����
     * @param  mixed  $field ��ѯ�ֶ�
     * @param  string $logic Logic
     * @param  array  $binds ������
     * @return string
     */
    protected function parseMultiWhereField(Query $query, $value, $field, string $logic, array $binds): string
    {
        array_unshift($value, $field);

        $where = [];
        foreach ($value as $item) {
            $where[] = $this->parseWhereItem($query, array_shift($item), $item, $binds);
        }

        return ' ' . $logic . ' ( ' . implode(' AND ', $where) . ' )';
    }

    /**
     * where�ӵ�Ԫ����
     * @access protected
     * @param  Query $query ��ѯ����
     * @param  mixed $field ��ѯ�ֶ�
     * @param  array $val   ��ѯ����
     * @param  array $binds ������
     * @return string
     */
    protected function parseWhereItem(Query $query, $field, array $val, array $binds = []): string
    {
        // �ֶη���
        $key = $field ? $this->parseKey($query, $field, true) : '';

        list($exp, $value) = $val;

        // ��������
        if (!is_string($exp)) {
            throw new Exception('where express error:' . var_export($exp, true));
        }

        $exp = strtoupper($exp);
        if (isset($this->exp[$exp])) {
            $exp = $this->exp[$exp];
        }

        if (is_string($field) && 'LIKE' != $exp) {
            $bindType = $binds[$field] ?? PDO::PARAM_STR;
        } else {
            $bindType = PDO::PARAM_STR;
        }

        if ($value instanceof Raw) {

        } elseif (is_object($value) && method_exists($value, '__toString')) {
            // ��������д��
            $value = $value->__toString();
        }

        if (is_scalar($value) && !in_array($exp, ['EXP', 'NOT NULL', 'NULL', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN']) && strpos($exp, 'TIME') === false) {
            if (is_string($value) && 0 === strpos($value, ':') && $query->isBind(substr($value, 1))) {
            } else {
                $name  = $query->bindValue($value, $bindType);
                $value = ':' . $name;
            }
        }

        // ������ѯ���ʽ
        foreach ($this->parser as $fun => $parse) {
            if (in_array($exp, $parse)) {
                return $this->$fun($query, $key, $exp, $value, $field, $bindType, $val[2] ?? 'AND');
            }
        }

        throw new Exception('where express error:' . $exp);
    }

    /**
     * ģ����ѯ
     * @access protected
     * @param  Query   $query   ��ѯ����
     * @param  string  $key
     * @param  string  $exp
     * @param  array   $value
     * @param  string  $field
     * @param  integer $bindType
     * @param  string  $logic
     * @return string
     */
    protected function parseLike(Query $query, string $key, string $exp, $value, $field, int $bindType, string $logic): string
    {
        // ģ��ƥ��
        if (is_array($value)) {
            $array = [];
            foreach ($value as $item) {
                $name    = $query->bindValue($item, PDO::PARAM_STR);
                $array[] = $key . ' ' . $exp . ' :' . $name;
            }

            $whereStr = '(' . implode(' ' . strtoupper($logic) . ' ', $array) . ')';
        } else {
            $whereStr = $key . ' ' . $exp . ' ' . $value;
        }

        return $whereStr;
    }

    /**
     * ���ʽ��ѯ
     * @access protected
     * @param  Query   $query   ��ѯ����
     * @param  string  $key
     * @param  string  $exp
     * @param  array   $value
     * @param  string  $field
     * @param  integer $bindType
     * @return string
     */
    protected function parseExp(Query $query, string $key, string $exp, Raw $value, string $field, int $bindType): string
    {
        // ���ʽ��ѯ
        return '( ' . $key . ' ' . $value->getValue() . ' )';
    }

    /**
     * ���ʽ��ѯ
     * @access protected
     * @param  Query   $query   ��ѯ����
     * @param  string  $key
     * @param  string  $exp
     * @param  array   $value
     * @param  string  $field
     * @param  integer $bindType
     * @return string
     */
    protected function parseColumn(Query $query, string $key, $exp, array $value, string $field, int $bindType): string
    {
        // �ֶαȽϲ�ѯ
        list($op, $field) = $value;

        if (!in_array(trim($op), ['=', '<>', '>', '>=', '<', '<='])) {
            throw new Exception('where express error:' . var_export($value, true));
        }

        return '( ' . $key . ' ' . $op . ' ' . $this->parseKey($query, $field, true) . ' )';
    }

    /**
     * Null��ѯ
     * @access protected
     * @param  Query   $query   ��ѯ����
     * @param  string  $key
     * @param  string  $exp
     * @param  mixed   $value
     * @param  string  $field
     * @param  integer $bindType
     * @return string
     */
    protected function parseNull(Query $query, string $key, string $exp, $value, $field, int $bindType): string
    {
        // NULL ��ѯ
        return $key . ' IS ' . $exp;
    }

    /**
     * ��Χ��ѯ
     * @access protected
     * @param  Query   $query   ��ѯ����
     * @param  string  $key
     * @param  string  $exp
     * @param  mixed   $value
     * @param  string  $field
     * @param  integer $bindType
     * @return string
     */
    protected function parseBetween(Query $query, string $key, string $exp, $value, $field, int $bindType): string
    {
        // BETWEEN ��ѯ
        $data = is_array($value) ? $value : explode(',', $value);

        $min = $query->bindValue($data[0], $bindType);
        $max = $query->bindValue($data[1], $bindType);

        return $key . ' ' . $exp . ' :' . $min . ' AND :' . $max . ' ';
    }

    /**
     * Exists��ѯ
     * @access protected
     * @param  Query   $query   ��ѯ����
     * @param  string  $key
     * @param  string  $exp
     * @param  mixed   $value
     * @param  string  $field
     * @param  integer $bindType
     * @return string
     */
    protected function parseExists(Query $query, string $key, string $exp, $value, string $field, int $bindType): string
    {
        // EXISTS ��ѯ
        if ($value instanceof Closure) {
            $value = $this->parseClosure($query, $value, false);
        } elseif ($value instanceof Raw) {
            $value = $value->getValue();
        } else {
            throw new Exception('where express error:' . $value);
        }

        return $exp . ' ( ' . $value . ' )';
    }

    /**
     * ʱ��Ƚϲ�ѯ
     * @access protected
     * @param  Query   $query  ��ѯ����
     * @param  string  $key
     * @param  string  $exp
     * @param  mixed   $value
     * @param  string  $field
     * @param  integer $bindType
     * @return string
     */
    protected function parseTime(Query $query, string $key, string $exp, $value, $field, int $bindType): string
    {
        return $key . ' ' . substr($exp, 0, 2) . ' ' . $this->parseDateTime($query, $value, $field, $bindType);
    }

    /**
     * ��С�Ƚϲ�ѯ
     * @access protected
     * @param  Query   $query   ��ѯ����
     * @param  string  $key
     * @param  string  $exp
     * @param  mixed   $value
     * @param  string  $field
     * @param  integer $bindType
     * @return string
     */
    protected function parseCompare(Query $query, string $key, string $exp, $value, $field, int $bindType): string
    {
        if (is_array($value)) {
            throw new Exception('where express error:' . $exp . var_export($value, true));
        }

        // �Ƚ�����
        if ($value instanceof Closure) {
            $value = $this->parseClosure($query, $value);
        }

        if ('=' == $exp && is_null($value)) {
            return $key . ' IS NULL';
        }

        return $key . ' ' . $exp . ' ' . $value;
    }

    /**
     * ʱ�䷶Χ��ѯ
     * @access protected
     * @param  Query   $query     ��ѯ����
     * @param  string  $key
     * @param  string  $exp
     * @param  mixed   $value
     * @param  string  $field
     * @param  integer $bindType
     * @return string
     */
    protected function parseBetweenTime(Query $query, string $key, string $exp, $value, $field, int $bindType): string
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }

        return $key . ' ' . substr($exp, 0, -4)
        . $this->parseDateTime($query, $value[0], $field, $bindType)
        . ' AND '
        . $this->parseDateTime($query, $value[1], $field, $bindType);

    }

    /**
     * IN��ѯ
     * @access protected
     * @param  Query   $query   ��ѯ����
     * @param  string  $key
     * @param  string  $exp
     * @param  mixed   $value
     * @param  string  $field
     * @param  integer $bindType
     * @return string
     */
    protected function parseIn(Query $query, string $key, string $exp, $value, $field, int $bindType): string
    {
        // IN ��ѯ
        if ($value instanceof Closure) {
            $value = $this->parseClosure($query, $value, false);
        } elseif ($value instanceof Raw) {
            $value = $value->getValue();
        } else {
            $value = array_unique(is_array($value) ? $value : explode(',', $value));
            $array = [];

            foreach ($value as $v) {
                $name    = $query->bindValue($v, $bindType);
                $array[] = ':' . $name;
            }

            if (count($array) == 1) {
                return $key . ('IN' == $exp ? ' = ' : ' <> ') . $array[0];
            } else {
                $zone  = implode(',', $array);
                $value = empty($zone) ? "''" : $zone;
            }
        }

        return $key . ' ' . $exp . ' (' . $value . ')';
    }

    /**
     * �հ��Ӳ�ѯ
     * @access protected
     * @param  Query    $query ��ѯ����
     * @param  \Closure $call
     * @param  bool     $show
     * @return string
     */
    protected function parseClosure(Query $query, Closure $call, bool $show = true): string
    {
        $newQuery = $query->newQuery()->setConnection($this->connection);
        $call($newQuery);

        return $newQuery->buildSql($show);
    }

    /**
     * ����ʱ����������
     * @access protected
     * @param  Query   $query ��ѯ����
     * @param  mixed   $value
     * @param  string  $key
     * @param  integer $bindType
     * @return string
     */
    protected function parseDateTime(Query $query, $value, string $key, int $bindType): string
    {
        $options = $query->getOptions();

        // ��ȡʱ���ֶ�����
        if (strpos($key, '.')) {
            list($table, $key) = explode('.', $key);

            if (isset($options['alias']) && $pos = array_search($table, $options['alias'])) {
                $table = $pos;
            }
        } else {
            $table = $options['table'];
        }

        $type = $query->getFieldType($key);

        if ($type) {
            if (is_string($value)) {
                $value = strtotime($value) ?: $value;
            }

            if (is_int($value)) {
                if (preg_match('/(datetime|timestamp)/is', $type)) {
                    // ���ڼ�ʱ�������
                    $value = date('Y-m-d H:i:s', $value);
                } elseif (preg_match('/(date)/is', $type)) {
                    // ���ڼ�ʱ�������
                    $value = date('Y-m-d', $value);
                }
            }
        }

        $name = $query->bindValue($value, $bindType);

        return ':' . $name;
    }

    /**
     * limit����
     * @access protected
     * @param  Query $query ��ѯ����
     * @param  mixed $limit
     * @return string
     */
    protected function parseLimit(Query $query, string $limit): string
    {
        return (!empty($limit) && false === strpos($limit, '(')) ? ' LIMIT ' . $limit . ' ' : '';
    }

    /**
     * join����
     * @access protected
     * @param  Query $query ��ѯ����
     * @param  array $join
     * @return string
     */
    protected function parseJoin(Query $query, array $join): string
    {
        $joinStr = '';

        foreach ($join as $item) {
            list($table, $type, $on) = $item;

            if (strpos($on, '=')) {
                list($val1, $val2) = explode('=', $on, 2);

                $condition = $this->parseKey($query, $val1) . '=' . $this->parseKey($query, $val2);
            } else {
                $condition = $on;
            }

            $table = $this->parseTable($query, $table);

            $joinStr .= ' ' . $type . ' JOIN ' . $table . ' ON ' . $condition;
        }

        return $joinStr;
    }

    /**
     * order����
     * @access protected
     * @param  Query $query ��ѯ����
     * @param  array $order
     * @return string
     */
    protected function parseOrder(Query $query, array $order): string
    {
        $array = [];
        foreach ($order as $key => $val) {
            if ($val instanceof Raw) {
                $array[] = $val->getValue();
            } elseif (is_array($val) && preg_match('/^[\w\.]+$/', $key)) {
                $array[] = $this->parseOrderField($query, $key, $val);
            } elseif ('[rand]' == $val) {
                $array[] = $this->parseRand($query);
            } elseif (is_string($val)) {
                if (is_numeric($key)) {
                    list($key, $sort) = explode(' ', strpos($val, ' ') ? $val : $val . ' ');
                } else {
                    $sort = $val;
                }

                if (preg_match('/^[\w\.]+$/', $key)) {
                    $sort    = strtoupper($sort);
                    $sort    = in_array($sort, ['ASC', 'DESC'], true) ? ' ' . $sort : '';
                    $array[] = $this->parseKey($query, $key, true) . $sort;
                } else {
                    throw new Exception('order express error:' . $key);
                }
            }
        }

        return empty($array) ? '' : ' ORDER BY ' . implode(',', $array);
    }

    /**
     * �������
     * @access protected
     * @param  Query $query ��ѯ����
     * @return string
     */
    protected function parseRand(Query $query): string
    {
        return '';
    }

    /**
     * orderField����
     * @access protected
     * @param  Query  $query ��ѯ����
     * @param  string $key
     * @param  array  $val
     * @return string
     */
    protected function parseOrderField(Query $query, string $key, array $val): string
    {
        if (isset($val['sort'])) {
            $sort = $val['sort'];
            unset($val['sort']);
        } else {
            $sort = '';
        }

        $sort = strtoupper($sort);
        $sort = in_array($sort, ['ASC', 'DESC'], true) ? ' ' . $sort : '';
        $bind = $query->getFieldsBindType();

        foreach ($val as $item) {
            $val[] = $this->parseDataBind($query, $key, $item, $bind);
        }

        return 'field(' . $this->parseKey($query, $key, true) . ',' . implode(',', $val) . ')' . $sort;
    }

    /**
     * group����
     * @access protected
     * @param  Query $query ��ѯ����
     * @param  mixed $group
     * @return string
     */
    protected function parseGroup(Query $query, $group): string
    {
        if (empty($group)) {
            return '';
        }

        if (is_string($group)) {
            $group = explode(',', $group);
        }

        $val = [];
        foreach ($group as $key) {
            $val[] = $this->parseKey($query, $key);
        }

        return ' GROUP BY ' . implode(',', $val);
    }

    /**
     * having����
     * @access protected
     * @param  Query  $query  ��ѯ����
     * @param  string $having
     * @return string
     */
    protected function parseHaving(Query $query, string $having): string
    {
        return !empty($having) ? ' HAVING ' . $having : '';
    }

    /**
     * comment����
     * @access protected
     * @param  Query  $query  ��ѯ����
     * @param  string $comment
     * @return string
     */
    protected function parseComment(Query $query, string $comment): string
    {
        if (false !== strpos($comment, '*/')) {
            $comment = strstr($comment, '*/', true);
        }

        return !empty($comment) ? ' /* ' . $comment . ' */' : '';
    }

    /**
     * distinct����
     * @access protected
     * @param  Query $query  ��ѯ����
     * @param  mixed $distinct
     * @return string
     */
    protected function parseDistinct(Query $query, bool $distinct): string
    {
        return !empty($distinct) ? ' DISTINCT ' : '';
    }

    /**
     * union����
     * @access protected
     * @param  Query $query ��ѯ����
     * @param  array $union
     * @return string
     */
    protected function parseUnion(Query $query, array $union): string
    {
        if (empty($union)) {
            return '';
        }

        $type = $union['type'];
        unset($union['type']);

        foreach ($union as $u) {
            if ($u instanceof Closure) {
                $sql[] = $type . ' ' . $this->parseClosure($query, $u);
            } elseif (is_string($u)) {
                $sql[] = $type . ' ( ' . $u . ' )';
            }
        }

        return ' ' . implode(' ', $sql);
    }

    /**
     * index���������ڲ�������ָ����Ҫǿ��ʹ�õ�����
     * @access protected
     * @param  Query $query ��ѯ����
     * @param  mixed $index
     * @return string
     */
    protected function parseForce(Query $query, $index): string
    {
        if (empty($index)) {
            return '';
        }

        if (is_array($index)) {
            $index = join(',', $index);
        }

        return sprintf(" FORCE INDEX ( %s ) ", $index);
    }

    /**
     * ����������
     * @access protected
     * @param  Query       $query ��ѯ����
     * @param  bool|string $lock
     * @return string
     */
    protected function parseLock(Query $query, $lock = false): string
    {
        if (is_bool($lock)) {
            return $lock ? ' FOR UPDATE ' : '';
        }

        if (is_string($lock) && !empty($lock)) {
            return ' ' . trim($lock) . ' ';
        } else {
            return '';
        }
    }

    /**
     * ���ɲ�ѯSQL
     * @access public
     * @param  Query $query ��ѯ����
     * @param  bool  $one   �Ƿ����ȡһ����¼
     * @return string
     */
    public function select(Query $query, bool $one = false): string
    {
        $options = $query->getOptions();

        return str_replace(
            ['%TABLE%', '%DISTINCT%', '%EXTRA%', '%FIELD%', '%JOIN%', '%WHERE%', '%GROUP%', '%HAVING%', '%ORDER%', '%LIMIT%', '%UNION%', '%LOCK%', '%COMMENT%', '%FORCE%'],
            [
                $this->parseTable($query, $options['table']),
                $this->parseDistinct($query, $options['distinct']),
                $this->parseExtra($query, $options['extra']),
                $this->parseField($query, $options['field']),
                $this->parseJoin($query, $options['join']),
                $this->parseWhere($query, $options['where']),
                $this->parseGroup($query, $options['group']),
                $this->parseHaving($query, $options['having']),
                $this->parseOrder($query, $options['order']),
                $this->parseLimit($query, $one ? '1' : $options['limit']),
                $this->parseUnion($query, $options['union']),
                $this->parseLock($query, $options['lock']),
                $this->parseComment($query, $options['comment']),
                $this->parseForce($query, $options['force']),
            ],
            $this->selectSql);
    }

    /**
     * ����Insert SQL
     * @access public
     * @param  Query $query ��ѯ����
     * @return string
     */
    public function insert(Query $query): string
    {
        $options = $query->getOptions();

        // ��������������
        $data = $this->parseData($query, $options['data']);
        if (empty($data)) {
            return '';
        }

        $fields = array_keys($data);
        $values = array_values($data);

        return str_replace(
            ['%INSERT%', '%TABLE%', '%EXTRA%', '%FIELD%', '%DATA%', '%COMMENT%'],
            [
                !empty($options['replace']) ? 'REPLACE' : 'INSERT',
                $this->parseTable($query, $options['table']),
                $this->parseExtra($query, $options['extra']),
                implode(' , ', $fields),
                implode(' , ', $values),
                $this->parseComment($query, $options['comment']),
            ],
            $this->insertSql);
    }

    /**
     * ����insertall SQL
     * @access public
     * @param  Query $query   ��ѯ����
     * @param  array $dataSet ���ݼ�
     * @return string
     */
    public function insertAll(Query $query, array $dataSet): string
    {
        $options = $query->getOptions();

        // ��ȡ����Ϣ
        $bind = $query->getFieldsBindType();

        // ��ȡ�Ϸ����ֶ�
        if ('*' == $options['field']) {
            $allowFields = array_keys($bind);
        } else {
            $allowFields = $options['field'];
        }

        $fields = [];
        $values = [];

        foreach ($dataSet as $k => $data) {
            $data = $this->parseData($query, $data, $allowFields, $bind);

            $values[] = 'SELECT ' . implode(',', array_values($data));

            if (!isset($insertFields)) {
                $insertFields = array_keys($data);
            }
        }

        foreach ($insertFields as $field) {
            $fields[] = $this->parseKey($query, $field);
        }

        return str_replace(
            ['%INSERT%', '%TABLE%', '%EXTRA%', '%FIELD%', '%DATA%', '%COMMENT%'],
            [
                !empty($options['replace']) ? 'REPLACE' : 'INSERT',
                $this->parseTable($query, $options['table']),
                $this->parseExtra($query, $options['extra']),
                implode(' , ', $fields),
                implode(' UNION ALL ', $values),
                $this->parseComment($query, $options['comment']),
            ],
            $this->insertAllSql);
    }

    /**
     * ����slect insert SQL
     * @access public
     * @param  Query  $query  ��ѯ����
     * @param  array  $fields ����
     * @param  string $table  ���ݱ�
     * @return string
     */
    public function selectInsert(Query $query, array $fields, string $table): string
    {
        foreach ($fields as &$field) {
            $field = $this->parseKey($query, $field, true);
        }

        return 'INSERT INTO ' . $this->parseTable($query, $table) . ' (' . implode(',', $fields) . ') ' . $this->select($query);
    }

    /**
     * ����update SQL
     * @access public
     * @param  Query $query ��ѯ����
     * @return string
     */
    public function update(Query $query): string
    {
        $options = $query->getOptions();

        $data = $this->parseData($query, $options['data']);

        if (empty($data)) {
            return '';
        }

        $set = [];
        foreach ($data as $key => $val) {
            $set[] = $key . ' = ' . $val;
        }

        return str_replace(
            ['%TABLE%', '%EXTRA%', '%SET%', '%JOIN%', '%WHERE%', '%ORDER%', '%LIMIT%', '%LOCK%', '%COMMENT%'],
            [
                $this->parseTable($query, $options['table']),
                $this->parseExtra($query, $options['extra']),
                implode(' , ', $set),
                $this->parseJoin($query, $options['join']),
                $this->parseWhere($query, $options['where']),
                $this->parseOrder($query, $options['order']),
                $this->parseLimit($query, $options['limit']),
                $this->parseLock($query, $options['lock']),
                $this->parseComment($query, $options['comment']),
            ],
            $this->updateSql);
    }

    /**
     * ����delete SQL
     * @access public
     * @param  Query $query ��ѯ����
     * @return string
     */
    public function delete(Query $query): string
    {
        $options = $query->getOptions();

        return str_replace(
            ['%TABLE%', '%EXTRA%', '%USING%', '%JOIN%', '%WHERE%', '%ORDER%', '%LIMIT%', '%LOCK%', '%COMMENT%'],
            [
                $this->parseTable($query, $options['table']),
                $this->parseExtra($query, $options['extra']),
                !empty($options['using']) ? ' USING ' . $this->parseTable($query, $options['using']) . ' ' : '',
                $this->parseJoin($query, $options['join']),
                $this->parseWhere($query, $options['where']),
                $this->parseOrder($query, $options['order']),
                $this->parseLimit($query, $options['limit']),
                $this->parseLock($query, $options['lock']),
                $this->parseComment($query, $options['comment']),
            ],
            $this->deleteSql);
    }
}
