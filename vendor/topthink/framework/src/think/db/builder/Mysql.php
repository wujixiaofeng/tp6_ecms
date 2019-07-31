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

namespace think\db\builder;

use think\db\Builder;
use think\db\Query;
use think\db\Raw;
use think\Exception;

/**
 * mysql���ݿ�����
 */
class Mysql extends Builder
{
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
        'parseRegexp'      => ['REGEXP', 'NOT REGEXP'],
        'parseNull'        => ['NOT NULL', 'NULL'],
        'parseBetweenTime' => ['BETWEEN TIME', 'NOT BETWEEN TIME'],
        'parseTime'        => ['< TIME', '> TIME', '<= TIME', '>= TIME'],
        'parseExists'      => ['NOT EXISTS', 'EXISTS'],
        'parseColumn'      => ['COLUMN'],
        'parseFindInSet'   => ['FIND IN SET'],
    ];

    /**
     * SELECT SQL���ʽ
     * @var string
     */
    protected $selectSql = 'SELECT%DISTINCT%%EXTRA% %FIELD% FROM %TABLE%%PARTITION%%FORCE%%JOIN%%WHERE%%GROUP%%HAVING%%UNION%%ORDER%%LIMIT% %LOCK%%COMMENT%';

    /**
     * INSERT SQL���ʽ
     * @var string
     */
    protected $insertSql = '%INSERT%%EXTRA% INTO %TABLE%%PARTITION% SET %SET% %DUPLICATE%%COMMENT%';

    /**
     * INSERT ALL SQL���ʽ
     * @var string
     */
    protected $insertAllSql = '%INSERT%%EXTRA% INTO %TABLE%%PARTITION% (%FIELD%) VALUES %DATA% %DUPLICATE%%COMMENT%';

    /**
     * UPDATE SQL���ʽ
     * @var string
     */
    protected $updateSql = 'UPDATE%EXTRA% %TABLE%%PARTITION% %JOIN% SET %SET% %WHERE% %ORDER%%LIMIT% %LOCK%%COMMENT%';

    /**
     * DELETE SQL���ʽ
     * @var string
     */
    protected $deleteSql = 'DELETE%EXTRA% FROM %TABLE%%PARTITION%%USING%%JOIN%%WHERE%%ORDER%%LIMIT% %LOCK%%COMMENT%';

    /**
     * ���ɲ�ѯSQL
     * @access public
     * @param  Query  $query  ��ѯ����
     * @param  bool   $one    �Ƿ����ȡһ����¼
     * @return string
     */
    public function select(Query $query, bool $one = false): string
    {
        $options = $query->getOptions();

        return str_replace(
            ['%TABLE%', '%PARTITION%', '%DISTINCT%', '%EXTRA%', '%FIELD%', '%JOIN%', '%WHERE%', '%GROUP%', '%HAVING%', '%ORDER%', '%LIMIT%', '%UNION%', '%LOCK%', '%COMMENT%', '%FORCE%'],
            [
                $this->parseTable($query, $options['table']),
                $this->parsePartition($query, $options['partition']),
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
     * @param  Query     $query   ��ѯ����
     * @param  bool      $replace �Ƿ�replace
     * @return string
     */
    public function insert(Query $query, bool $replace = false): string
    {
        $options = $query->getOptions();

        // ��������������
        $data = $this->parseData($query, $options['data']);
        if (empty($data)) {
            return '';
        }

        $set = [];
        foreach ($data as $key => $val) {
            $set[] = $key . ' = ' . $val;
        }

        return str_replace(
            ['%INSERT%', '%EXTRA%', '%TABLE%', '%PARTITION%', '%SET%', '%DUPLICATE%', '%COMMENT%'],
            [
                $replace ? 'REPLACE' : 'INSERT',
                $this->parseExtra($query, $options['extra']),
                $this->parseTable($query, $options['table']),
                $this->parsePartition($query, $options['partition']),
                implode(' , ', $set),
                $this->parseDuplicate($query, $options['duplicate']),
                $this->parseComment($query, $options['comment']),
            ],
            $this->insertSql);
    }

    /**
     * ����insertall SQL
     * @access public
     * @param  Query     $query   ��ѯ����
     * @param  array     $dataSet ���ݼ�
     * @param  bool      $replace �Ƿ�replace
     * @return string
     */
    public function insertAll(Query $query, array $dataSet, bool $replace = false): string
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

        foreach ($dataSet as $data) {
            $data = $this->parseData($query, $data, $allowFields, $bind);

            $values[] = '( ' . implode(',', array_values($data)) . ' )';

            if (!isset($insertFields)) {
                $insertFields = array_keys($data);
            }
        }

        foreach ($insertFields as $field) {
            $fields[] = $this->parseKey($query, $field);
        }

        return str_replace(
            ['%INSERT%', '%EXTRA%', '%TABLE%', '%PARTITION%', '%FIELD%', '%DATA%', '%DUPLICATE%', '%COMMENT%'],
            [
                $replace ? 'REPLACE' : 'INSERT',
                $this->parseExtra($query, $options['extra']),
                $this->parseTable($query, $options['table']),
                $this->parsePartition($query, $options['partition']),
                implode(' , ', $fields),
                implode(' , ', $values),
                $this->parseDuplicate($query, $options['duplicate']),
                $this->parseComment($query, $options['comment']),
            ],
            $this->insertAllSql);
    }

    /**
     * ����update SQL
     * @access public
     * @param  Query     $query  ��ѯ����
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
            ['%TABLE%', '%PARTITION%', '%EXTRA%', '%SET%', '%JOIN%', '%WHERE%', '%ORDER%', '%LIMIT%', '%LOCK%', '%COMMENT%'],
            [
                $this->parseTable($query, $options['table']),
                $this->parsePartition($query, $options['partition']),
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
     * @param  Query  $query  ��ѯ����
     * @return string
     */
    public function delete(Query $query): string
    {
        $options = $query->getOptions();

        return str_replace(
            ['%TABLE%', '%PARTITION%', '%EXTRA%', '%USING%', '%JOIN%', '%WHERE%', '%ORDER%', '%LIMIT%', '%LOCK%', '%COMMENT%'],
            [
                $this->parseTable($query, $options['table']),
                $this->parsePartition($query, $options['partition']),
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

    /**
     * �����ѯ
     * @access protected
     * @param  Query        $query        ��ѯ����
     * @param  string       $key
     * @param  string       $exp
     * @param  mixed        $value
     * @param  string       $field
     * @return string
     */
    protected function parseRegexp(Query $query, string $key, string $exp, $value, string $field): string
    {
        if ($value instanceof Raw) {
            $value = $value->getValue();
        }

        return $key . ' ' . $exp . ' ' . $value;
    }

    /**
     * FIND_IN_SET ��ѯ
     * @access protected
     * @param  Query        $query        ��ѯ����
     * @param  string       $key
     * @param  string       $exp
     * @param  mixed        $value
     * @param  string       $field
     * @return string
     */
    protected function parseFindInSet(Query $query, string $key, string $exp, $value, string $field): string
    {
        if ($value instanceof Raw) {
            $value = $value->getValue();
        }

        return 'FIND_IN_SET(' . $value . ', ' . $key . ')';
    }

    /**
     * �ֶκͱ�������
     * @access public
     * @param  Query     $query ��ѯ����
     * @param  mixed     $key   �ֶ���
     * @param  bool      $strict   �ϸ���
     * @return string
     */
    public function parseKey(Query $query, $key, bool $strict = false): string
    {
        if (is_int($key)) {
            return (string) $key;
        } elseif ($key instanceof Raw) {
            return $key->getValue();
        }

        $key = trim($key);

        if (strpos($key, '->') && false === strpos($key, '(')) {
            // JSON�ֶ�֧��
            list($field, $name) = explode('->', $key, 2);

            return 'json_extract(' . $this->parseKey($query, $field) . ', \'$.' . str_replace('->', '.', $name) . '\')';
        } elseif (strpos($key, '.') && !preg_match('/[,\'\"\(\)`\s]/', $key)) {
            list($table, $key) = explode('.', $key, 2);

            $alias = $query->getOptions('alias');

            if ('__TABLE__' == $table) {
                $table = $query->getOptions('table');
                $table = is_array($table) ? array_shift($table) : $table;
            }

            if (isset($alias[$table])) {
                $table = $alias[$table];
            }
        }

        if ($strict && !preg_match('/^[\w\.\*]+$/', $key)) {
            throw new Exception('not support data:' . $key);
        }

        if ('*' != $key && !preg_match('/[,\'\"\*\(\)`.\s]/', $key)) {
            $key = '`' . $key . '`';
        }

        if (isset($table)) {
            if (strpos($table, '.')) {
                $table = str_replace('.', '`.`', $table);
            }

            $key = '`' . $table . '`.' . $key;
        }

        return $key;
    }

    /**
     * �������
     * @access protected
     * @param  Query     $query        ��ѯ����
     * @return string
     */
    protected function parseRand(Query $query): string
    {
        return 'rand()';
    }

    /**
     * Partition ����
     * @access protected
     * @param  Query        $query    ��ѯ����
     * @param  string|array $partition  ����
     * @return string
     */
    protected function parsePartition(Query $query, $partition): string
    {
        if ('' == $partition) {
            return '';
        }

        if (is_string($partition)) {
            $partition = explode(',', $partition);
        }

        return ' PARTITION (' . implode(' , ', $partition) . ') ';
    }

    /**
     * ON DUPLICATE KEY UPDATE ����
     * @access protected
     * @param  Query  $query    ��ѯ����
     * @param  mixed  $duplicate
     * @return string
     */
    protected function parseDuplicate(Query $query, $duplicate): string
    {
        if ('' == $duplicate) {
            return '';
        }

        if ($duplicate instanceof Raw) {
            return ' ON DUPLICATE KEY UPDATE ' . $duplicate->getValue() . ' ';
        }

        if (is_string($duplicate)) {
            $duplicate = explode(',', $duplicate);
        }

        $updates = [];
        foreach ($duplicate as $key => $val) {
            if (is_numeric($key)) {
                $val       = $this->parseKey($query, $val);
                $updates[] = $val . ' = VALUES(' . $val . ')';
            } elseif ($val instanceof Raw) {
                $updates[] = $this->parseKey($query, $key) . " = " . $val->getValue();
            } else {
                $name      = $query->bindValue($val, $query->getFieldBindType($key));
                $updates[] = $this->parseKey($query, $key) . " = :" . $name;
            }
        }

        return ' ON DUPLICATE KEY UPDATE ' . implode(' , ', $updates) . ' ';
    }
}
