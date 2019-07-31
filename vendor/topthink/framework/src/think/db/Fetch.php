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

use think\App;
use think\Exception;

/**
 * SQL��ȡ��
 */
class Fetch
{
    /**
     * ��ѯ����
     * @var Query
     */
    protected $query;

    /**
     * Connection����
     * @var Connection
     */
    protected $connection;

    /**
     * Builder����
     * @var Builder
     */
    protected $builder;

    /**
     * ����һ����ѯSQL��ȡ����
     *
     * @param  Query    $query      ��ѯ����
     */
    public function __construct(Query $query)
    {
        $this->query      = $query;
        $this->connection = $query->getConnection();
        $this->builder    = $this->connection->getBuilder();
    }

    /**
     * �ۺϲ�ѯ
     * @access protected
     * @param  string $aggregate    �ۺϷ���
     * @param  string $field        �ֶ���
     * @return string
     */
    protected function aggregate(string $aggregate, string $field): string
    {
        $this->query->parseOptions();

        $field = $aggregate . '(' . $this->builder->parseKey($this->query, $field) . ') AS tp_' . strtolower($aggregate);

        return $this->value($field, 0, false);
    }

    /**
     * �õ�ĳ���ֶε�ֵ
     * @access public
     * @param  string $field   �ֶ���
     * @param  mixed  $default Ĭ��ֵ
     * @return string
     */
    public function value(string $field, $default = null, bool $one = true): string
    {
        $options = $this->query->parseOptions();

        if (isset($options['field'])) {
            $this->query->removeOption('field');
        }

        $this->query->setOption('field', (array) $field);

        // ���ɲ�ѯSQL
        $sql = $this->builder->select($this->query, $one);

        if (isset($options['field'])) {
            $this->query->setOption('field', $options['field']);
        } else {
            $this->query->removeOption('field');
        }

        return $this->fetch($sql);
    }

    /**
     * �õ�ĳ���е�����
     * @access public
     * @param  string $field �ֶ��� ����ֶ��ö��ŷָ�
     * @param  string $key   ����
     * @return string
     */
    public function column(string $field, string $key = ''): string
    {
        $options = $this->query->parseOptions();

        if (isset($options['field'])) {
            $this->query->removeOption('field');
        }

        if ($key && '*' != $field) {
            $field = $key . ',' . $field;
        }

        $field = array_map('trim', explode(',', $field));

        $this->query->setOption('field', $field);

        // ���ɲ�ѯSQL
        $sql = $this->builder->select($this->query);

        if (isset($options['field'])) {
            $this->query->setOption('field', $options['field']);
        } else {
            $this->query->removeOption('field');
        }

        return $this->fetch($sql);
    }

    /**
     * �����¼
     * @access public
     * @param  array $data ����
     * @return string
     */
    public function insert(array $data = []): string
    {
        $options = $this->query->parseOptions();

        if (!empty($data)) {
            $this->query->setOption('data', $data);
        }

        $sql = $this->builder->insert($this->query);

        return $this->fetch($sql);
    }

    /**
     * �����¼����ȡ����ID
     * @access public
     * @param  array $data ����
     * @return string
     */
    public function insertGetId(array $data = []): string
    {
        return $this->insert($data);
    }

    /**
     * �������� �Զ��ж�insert����update
     * @access public
     * @param  array $data        ����
     * @param  bool  $forceInsert �Ƿ�ǿ��insert
     * @return string
     */
    public function save(array $data = [], bool $forceInsert = false): string
    {
        if ($forceInsert) {
            return $this->insert($data);
        }

        $data = array_merge($this->query->getOptions('data') ?: [], $data);

        $this->query->setOption('data', $data);

        if ($this->query->getOptions('where')) {
            $isUpdate = true;
        } else {
            $isUpdate = $this->query->parseUpdateData($data);
        }

        return $isUpdate ? $this->update() : $this->insert();
    }

    /**
     * ���������¼
     * @access public
     * @param  array     $dataSet ���ݼ�
     * @param  integer   $limit   ÿ��д����������
     * @return string
     */
    public function insertAll(array $dataSet = [], int $limit = null): string
    {
        $options = $this->query->parseOptions();

        if (empty($dataSet)) {
            $dataSet = $options['data'];
        }

        if (empty($limit) && !empty($options['limit'])) {
            $limit = $options['limit'];
        }

        if ($limit) {
            $array    = array_chunk($dataSet, $limit, true);
            $fetchSql = [];
            foreach ($array as $item) {
                $sql  = $this->builder->insertAll($this->query, $item);
                $bind = $this->query->getBind();

                $fetchSql[] = $this->connection->getRealSql($sql, $bind);
            }

            return implode(';', $fetchSql);
        }

        $sql = $this->builder->insertAll($this->query, $dataSet);

        return $this->fetch($sql);
    }

    /**
     * ͨ��Select��ʽ�����¼
     * @access public
     * @param  array    $fields Ҫ��������ݱ��ֶ���
     * @param  string   $table  Ҫ��������ݱ���
     * @return string
     */
    public function selectInsert(array $fields, string $table): string
    {
        $this->query->parseOptions();

        $sql = $this->builder->selectInsert($this->query, $fields, $table);

        return $this->fetch($sql);
    }

    /**
     * ���¼�¼
     * @access public
     * @param  mixed $data ����
     * @return string
     */
    public function update(array $data = []): string
    {
        $options = $this->query->parseOptions();

        $data = !empty($data) ? $data : $options['data'];

        $pk = $this->query->getPk();

        if (empty($options['where'])) {
            // ��������������� ���Զ���Ϊ��������
            if (is_string($pk) && isset($data[$pk])) {
                $this->query->where($pk, '=', $data[$pk]);
                unset($data[$pk]);
            } elseif (is_array($pk)) {
                // ���Ӹ�������֧��
                foreach ($pk as $field) {
                    if (isset($data[$field])) {
                        $this->query->where($field, '=', $data[$field]);
                    } else {
                        // ���ȱ�ٸ�������������ִ��
                        throw new Exception('miss complex primary data');
                    }
                    unset($data[$field]);
                }
            }

            if (empty($this->query->getOptions('where'))) {
                // ���û���κθ���������ִ��
                throw new Exception('miss update condition');
            }
        }

        // ��������
        $this->query->setOption('data', $data);

        // ����UPDATE SQL���
        $sql = $this->builder->update($this->query);

        return $this->fetch($sql);
    }

    /**
     * ɾ����¼
     * @access public
     * @param  mixed $data ���ʽ true ��ʾǿ��ɾ��
     * @return string
     */
    public function delete($data = null): string
    {
        $options = $this->query->parseOptions();

        if (!is_null($data) && true !== $data) {
            // ARģʽ������������
            $this->query->parsePkWhere($data);
        }

        if (!empty($options['soft_delete'])) {
            // ��ɾ��
            list($field, $condition) = $options['soft_delete'];
            if ($condition) {
                $this->query->setOption('soft_delete', null);
                $this->query->setOption('data', [$field => $condition]);
                // ����ɾ��SQL���
                $sql = $this->builder->delete($this->query);
                return $this->fetch($sql);
            }
        }

        // ����ɾ��SQL���
        $sql = $this->builder->delete($this->query);

        return $this->fetch($sql);
    }

    /**
     * ���Ҽ�¼ ����SQL
     * @access public
     * @param  mixed $data
     * @return string
     */
    public function select($data = null): string
    {
        $this->query->parseOptions();

        if (!is_null($data)) {
            // ������������
            $this->query->parsePkWhere($data);
        }

        // ���ɲ�ѯSQL
        $sql = $this->builder->select($this->query);

        return $this->fetch($sql);
    }

    /**
     * ���ҵ�����¼ ����SQL���
     * @access public
     * @param  mixed $data
     * @return string
     */
    public function find($data = null): string
    {
        $this->query->parseOptions();

        if (!is_null($data)) {
            // ARģʽ������������
            $this->query->parsePkWhere($data);
        }

        // ���ɲ�ѯSQL
        $sql = $this->builder->select($this->query, true);

        // ��ȡʵ��ִ�е�SQL���
        return $this->fetch($sql);
    }

    /**
     * ���Ҷ�����¼ ������������׳��쳣
     * @access public
     * @param  mixed $data
     * @return string
     */
    public function selectOrFail($data = null): string
    {
        return $this->select($data);
    }

    /**
     * ���ҵ�����¼ ������������׳��쳣
     * @access public
     * @param  mixed $data
     * @return string
     */
    public function findOrFail($data = null): string
    {
        return $this->find($data);
    }

    /**
     * ���ҵ�����¼ �����ڷ��ؿ����ݣ����߿�ģ�ͣ�
     * @access public
     * @param  mixed $data ����
     * @return string
     */
    public function findOrEmpty($data = null)
    {
        return $this->find($data);
    }

    /**
     * ��ȡʵ�ʵ�SQL���
     * @access public
     * @param  string $sql
     * @return string
     */
    public function fetch(string $sql): string
    {
        $bind = $this->query->getBind();

        return $this->connection->getRealSql($sql, $bind);
    }

    /**
     * COUNT��ѯ
     * @access public
     * @param  string $field �ֶ���
     * @return string
     */
    public function count(string $field = '*'): string
    {
        $options = $this->query->parseOptions();

        if (!empty($options['group'])) {
            // ֧��GROUP
            $bind   = $this->query->getBind();
            $subSql = $this->query->options($options)->field('count(' . $field . ') AS think_count')->bind($bind)->buildSql();

            $query = $this->query->newQuery()->table([$subSql => '_group_count_']);

            return $query->fetchsql()->aggregate('COUNT', '*');
        } else {
            return $this->aggregate('COUNT', $field);
        }
    }

    /**
     * SUM��ѯ
     * @access public
     * @param  string $field �ֶ���
     * @return string
     */
    public function sum(string $field): string
    {
        return $this->aggregate('SUM', $field);
    }

    /**
     * MIN��ѯ
     * @access public
     * @param  string $field    �ֶ���
     * @return string
     */
    public function min(string $field): string
    {
        return $this->aggregate('MIN', $field);
    }

    /**
     * MAX��ѯ
     * @access public
     * @param  string $field    �ֶ���
     * @return string
     */
    public function max(string $field): string
    {
        return $this->aggregate('MAX', $field);
    }

    /**
     * AVG��ѯ
     * @access public
     * @param  string $field �ֶ���
     * @return string
     */
    public function avg(string $field): string
    {
        return $this->aggregate('AVG', $field);
    }

    public function __call($method, $args)
    {
        if (strtolower(substr($method, 0, 5)) == 'getby') {
            // ����ĳ���ֶλ�ȡ��¼
            $field = App::parseName(substr($method, 5));
            return $this->where($field, '=', $args[0])->find();
        } elseif (strtolower(substr($method, 0, 10)) == 'getfieldby') {
            // ����ĳ���ֶλ�ȡ��¼��ĳ��ֵ
            $name = App::parseName(substr($method, 10));
            return $this->where($name, '=', $args[0])->value($args[1]);
        }

        $result = call_user_func_array([$this->query, $method], $args);
        return $result === $this->query ? $this : $result;
    }
}
