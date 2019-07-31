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

use PDO;
use PDOStatement;
use think\Cache;
use think\cache\CacheItem;
use think\Container;
use think\Db;
use think\db\exception\BindParamException;
use think\Exception;
use think\exception\PDOException;
use think\Log;

/**
 * ���ݿ����ӻ�����
 */
abstract class Connection
{
    const PARAM_FLOAT = 21;

    /**
     * PDO����ʵ��
     * @var PDOStatement
     */
    protected $PDOStatement;

    /**
     * ��ǰSQLָ��
     * @var string
     */
    protected $queryStr = '';

    /**
     * ���ػ���Ӱ���¼��
     * @var int
     */
    protected $numRows = 0;

    /**
     * ����ָ����
     * @var int
     */
    protected $transTimes = 0;

    /**
     * ������Ϣ
     * @var string
     */
    protected $error = '';

    /**
     * ���ݿ�����ID ֧�ֶ������
     * @var PDO[]
     */
    protected $links = [];

    /**
     * ��ǰ����ID
     * @var PDO
     */
    protected $linkID;

    /**
     * ��ǰ������ID
     * @var PDO
     */
    protected $linkRead;

    /**
     * ��ǰд����ID
     * @var PDO
     */
    protected $linkWrite;

    /**
     * ��ѯ�������
     * @var int
     */
    protected $fetchType = PDO::FETCH_ASSOC;

    /**
     * �ֶ����Դ�Сд
     * @var int
     */
    protected $attrCase = PDO::CASE_LOWER;

    /**
     * ���ݱ���Ϣ
     * @var array
     */
    protected $info = [];

    /**
     * ��ѯ��ʼʱ��
     * @var float
     */
    protected $queryStartTime;

    /**
     * Builder����
     * @var Builder
     */
    protected $builder;

    /**
     * Db����
     * @var Db
     */
    protected $db;

    /**
     * �Ƿ��ȡ����
     * @var bool
     */
    protected $readMaster = false;

    /**
     * ���ݿ����Ӳ�������
     * @var array
     */
    protected $config = [
        // ���ݿ�����
        'type'            => '',
        // ��������ַ
        'hostname'        => '',
        // ���ݿ���
        'database'        => '',
        // �û���
        'username'        => '',
        // ����
        'password'        => '',
        // �˿�
        'hostport'        => '',
        // ����dsn
        'dsn'             => '',
        // ���ݿ����Ӳ���
        'params'          => [],
        // ���ݿ����Ĭ�ϲ���utf8
        'charset'         => 'utf8',
        // ���ݿ��ǰ׺
        'prefix'          => '',
        // ���ݿ����ģʽ
        'debug'           => false,
        // ���ݿⲿ��ʽ:0 ����ʽ(��һ������),1 �ֲ�ʽ(���ӷ�����)
        'deploy'          => 0,
        // ���ݿ��д�Ƿ���� ����ʽ��Ч
        'rw_separate'     => false,
        // ��д����� ������������
        'master_num'      => 1,
        // ָ���ӷ��������
        'slave_no'        => '',
        // ģ��д����Զ���ȡ��������
        'read_master'     => false,
        // �Ƿ��ϸ����ֶ��Ƿ����
        'fields_strict'   => true,
        // �Զ�д��ʱ����ֶ�
        'auto_timestamp'  => false,
        // ʱ���ֶ�ȡ�����Ĭ��ʱ���ʽ
        'datetime_format' => 'Y-m-d H:i:s',
        // �Ƿ���Ҫ����SQL���ܷ���
        'sql_explain'     => false,
        // Builder��
        'builder'         => '',
        // Query��
        'query'           => '',
        // �Ƿ���Ҫ��������
        'break_reconnect' => false,
        // ���߱�ʶ�ַ���
        'break_match_str' => [],
    ];

    /**
     * PDO���Ӳ���
     * @var array
     */
    protected $params = [
        PDO::ATTR_CASE              => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS      => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES  => false,
    ];

    /**
     * ����������ӳ��
     * @var array
     */
    protected $bindType = [
        'string'  => PDO::PARAM_STR,
        'str'     => PDO::PARAM_STR,
        'integer' => PDO::PARAM_INT,
        'int'     => PDO::PARAM_INT,
        'boolean' => PDO::PARAM_BOOL,
        'bool'    => PDO::PARAM_BOOL,
        'float'   => self::PARAM_FLOAT,
    ];

    /**
     * ���������߱�ʶ�ַ�
     * @var array
     */
    protected $breakMatchStr = [
        'server has gone away',
        'no connection to the server',
        'Lost connection',
        'is dead or not enabled',
        'Error while sending',
        'decryption failed or bad record mac',
        'server closed the connection unexpectedly',
        'SSL connection has been closed unexpectedly',
        'Error writing data to the connection',
        'Resource deadlock avoided',
        'failed with errno',
    ];

    /**
     * �󶨲���
     * @var array
     */
    protected $bind = [];

    /**
     * �������
     * @var Cache
     */
    protected $cache;

    /**
     * ��־����
     * @var Log
     */
    protected $log;

    /**
     * �ܹ����� ��ȡ���ݿ�������Ϣ
     * @access public
     * @param Cache $cache �������
     * @param Log   $log ��־����
     * @param array $config ���ݿ���������
     */
    public function __construct(Cache $cache, Log $log, array $config = [])
    {
        if (!empty($config)) {
            $this->config = array_merge($this->config, $config);
        }

        // ����Builder����
        $class = $this->getBuilderClass();

        $this->builder = new $class($this);
        $this->cache   = $cache;
        $this->log     = $log;

        // ִ�г�ʼ������
        $this->initialize();
    }

    /**
     * ��ʼ��
     * @access protected
     * @return void
     */
    protected function initialize(): void
    {
    }

    /**
     * ��ȡ��ǰ���������Ӧ��Query��
     * @access public
     * @return string
     */
    public function getQueryClass(): string
    {
        return $this->getConfig('query') ?: Query::class;
    }

    /**
     * ��ȡ��ǰ���������Ӧ��Builder��
     * @access public
     * @return string
     */
    public function getBuilderClass(): string
    {
        return $this->getConfig('builder') ?: '\\think\\db\\builder\\' . ucfirst($this->getConfig('type'));
    }

    /**
     * ���õ�ǰ�����ݿ�Builder����
     * @access protected
     * @param Builder $builder
     * @return void
     */
    protected function setBuilder(Builder $builder): void
    {
        $this->builder = $builder;
    }

    /**
     * ��ȡ��ǰ��builderʵ������
     * @access public
     * @return Builder
     */
    public function getBuilder(): Builder
    {
        return $this->builder;
    }

    /**
     * ���õ�ǰ�����ݿ�Db����
     * @access public
     * @param Db $db
     * @return void
     */
    public function setDb(Db $db): void
    {
        $this->db = $db;
    }

    /**
     * ����pdo���ӵ�dsn��Ϣ
     * @access protected
     * @param array $config ������Ϣ
     * @return string
     */
    abstract protected function parseDsn(array $config);

    /**
     * ȡ�����ݱ���ֶ���Ϣ
     * @access public
     * @param string $tableName ���ݱ�����
     * @return array
     */
    abstract public function getFields(string $tableName);

    /**
     * ȡ�����ݿ�ı���Ϣ
     * @access public
     * @param string $dbName ���ݿ�����
     * @return array
     */
    abstract public function getTables(string $dbName);

    /**
     * SQL���ܷ���
     * @access protected
     * @param string $sql SQL���
     * @return array
     */
    abstract protected function getExplain(string $sql);

    /**
     * �Է����ݱ��ֶ���Ϣ���д�Сдת������
     * @access public
     * @param array $info �ֶ���Ϣ
     * @return array
     */
    public function fieldCase(array $info): array
    {
        // �ֶδ�Сдת��
        switch ($this->attrCase) {
            case PDO::CASE_LOWER:
                $info = array_change_key_case($info);
                break;
            case PDO::CASE_UPPER:
                $info = array_change_key_case($info, CASE_UPPER);
                break;
            case PDO::CASE_NATURAL:
            default:
                // ����ת��
        }

        return $info;
    }

    /**
     * ��ȡ�ֶΰ�����
     * @access public
     * @param string $type �ֶ�����
     * @return integer
     */
    public function getFieldBindType(string $type): int
    {
        if (in_array($type, ['integer', 'string', 'float', 'boolean', 'bool', 'int', 'str'])) {
            $bind = $this->bindType[$type];
        } elseif (0 === strpos($type, 'set') || 0 === strpos($type, 'enum')) {
            $bind = PDO::PARAM_STR;
        } elseif (preg_match('/(double|float|decimal|real|numeric)/is', $type)) {
            $bind = self::PARAM_FLOAT;
        } elseif (preg_match('/(int|serial|bit)/is', $type)) {
            $bind = PDO::PARAM_INT;
        } elseif (preg_match('/bool/is', $type)) {
            $bind = PDO::PARAM_BOOL;
        } else {
            $bind = PDO::PARAM_STR;
        }

        return $bind;
    }

    /**
     * ��ȡ���ݱ���Ϣ
     * @access public
     * @param mixed  $tableName ���ݱ��� �����Զ���ȡ
     * @param string $fetch     ��ȡ��Ϣ���� ���� fields type bind pk
     * @return mixed
     */
    public function getTableInfo($tableName, string $fetch = '')
    {
        if (is_array($tableName)) {
            $tableName = key($tableName) ?: current($tableName);
        }

        if (strpos($tableName, ',')) {
            // �����ȡ�ֶ���Ϣ
            return [];
        }

        // �����Ӳ�ѯ��Ϊ����������
        if (strpos($tableName, ')')) {
            return [];
        }

        list($tableName) = explode(' ', $tableName);

        if (!strpos($tableName, '.')) {
            $schema = $this->getConfig('database') . '.' . $tableName;
        } else {
            $schema = $tableName;
        }

        if (!isset($this->info[$schema])) {
            // ��ȡ����
            $cacheFile = Container::pull('app')->getRuntimePath() . 'schema' . DIRECTORY_SEPARATOR . $schema . '.php';

            if (!$this->config['debug'] && is_file($cacheFile)) {
                $info = include $cacheFile;
            } else {
                $info = $this->getFields($tableName);
            }

            $fields = array_keys($info);
            $bind   = $type   = [];

            foreach ($info as $key => $val) {
                // ��¼�ֶ�����
                $type[$key] = $val['type'];
                $bind[$key] = $this->getFieldBindType($val['type']);

                if (!empty($val['primary'])) {
                    $pk[] = $key;
                }
            }

            if (isset($pk)) {
                // ��������
                $pk = count($pk) > 1 ? $pk : $pk[0];
            } else {
                $pk = null;
            }

            $this->info[$schema] = ['fields' => $fields, 'type' => $type, 'bind' => $bind, 'pk' => $pk];
        }

        return $fetch ? $this->info[$schema][$fetch] : $this->info[$schema];
    }

    /**
     * ��ȡ���ݱ������
     * @access public
     * @param mixed $tableName ���ݱ���
     * @return string|array
     */
    public function getPk($tableName)
    {
        return $this->getTableInfo($tableName, 'pk');
    }

    /**
     * ��ȡ���ݱ��ֶ���Ϣ
     * @access public
     * @param mixed $tableName ���ݱ���
     * @return array
     */
    public function getTableFields($tableName): array
    {
        return $this->getTableInfo($tableName, 'fields');
    }

    /**
     * ��ȡ���ݱ��ֶ�����
     * @access public
     * @param mixed  $tableName ���ݱ���
     * @param string $field     �ֶ���
     * @return array|string
     */
    public function getFieldsType($tableName, string $field = null)
    {
        $result = $this->getTableInfo($tableName, 'type');

        if ($field && isset($result[$field])) {
            return $result[$field];
        }

        return $result;
    }

    /**
     * ��ȡ���ݱ����Ϣ
     * @access public
     * @param mixed $tableName ���ݱ���
     * @return array
     */
    public function getFieldsBind($tableName): array
    {
        return $this->getTableInfo($tableName, 'bind');
    }

    /**
     * ��ȡ���ݿ�����ò���
     * @access public
     * @param string $config ��������
     * @return mixed
     */
    public function getConfig(string $config = '')
    {
        if ('' === $config) {
            return $this->config;
        }
        return $this->config[$config] ?? null;
    }

    /**
     * �������ݿ�����ò���
     * @access public
     * @param array $config ����
     * @return void
     */
    public function setConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * �������ݿⷽ��
     * @access public
     * @param array      $config         ���Ӳ���
     * @param integer    $linkNum        �������
     * @param array|bool $autoConnection �Ƿ��Զ����������ݿ⣨���ڷֲ�ʽ��
     * @return PDO
     * @throws Exception
     */
    public function connect(array $config = [], $linkNum = 0, $autoConnection = false): PDO
    {
        if (isset($this->links[$linkNum])) {
            return $this->links[$linkNum];
        }

        if (empty($config)) {
            $config = $this->config;
        } else {
            $config = array_merge($this->config, $config);
        }

        // ���Ӳ���
        if (isset($config['params']) && is_array($config['params'])) {
            $params = $config['params'] + $this->params;
        } else {
            $params = $this->params;
        }

        // ��¼��ǰ�ֶ����Դ�Сд����
        $this->attrCase = $params[PDO::ATTR_CASE];

        if (!empty($config['break_match_str'])) {
            $this->breakMatchStr = array_merge($this->breakMatchStr, (array) $config['break_match_str']);
        }

        try {
            if (empty($config['dsn'])) {
                $config['dsn'] = $this->parseDsn($config);
            }

            $startTime             = microtime(true);
            $this->links[$linkNum] = $this->createPdo($config['dsn'], $config['username'], $config['password'], $params);
            // ��¼���ݿ�������Ϣ
            $this->log('[ DB ] CONNECT:[ UseTime:' . number_format(microtime(true) - $startTime, 6) . 's ] ' . $config['dsn']);

            return $this->links[$linkNum];
        } catch (\PDOException $e) {
            if ($autoConnection) {
                $this->log->error($e->getMessage());
                return $this->connect($autoConnection, $linkNum);
            } else {
                throw $e;
            }
        }
    }

    /**
     * ����PDOʵ��
     * @param $dsn
     * @param $username
     * @param $password
     * @param $params
     * @return PDO
     */
    protected function createPdo($dsn, $username, $password, $params)
    {
        return new PDO($dsn, $username, $password, $params);
    }

    /**
     * �ͷŲ�ѯ���
     * @access public
     */
    public function free(): void
    {
        $this->PDOStatement = null;
    }

    /**
     * ��ȡPDO����
     * @access public
     * @return \PDO|false
     */
    public function getPdo()
    {
        if (!$this->linkID) {
            return false;
        }

        return $this->linkID;
    }

    /**
     * ִ�в�ѯ ʹ����������������
     * @access public
     * @param Query        $query     ��ѯ����
     * @param string       $sql       sqlָ��
     * @param array        $bind      ������
     * @param \think\Model $model     ģ�Ͷ���ʵ��
     * @param array        $condition ��ѯ����
     * @return \Generator
     */
    public function getCursor(Query $query, string $sql, array $bind = [], $model = null, $condition = null)
    {
        $this->queryPDOStatement($query, $sql, $bind);

        // ���ؽ����
        while ($result = $this->PDOStatement->fetch($this->fetchType)) {
            if ($model) {
                yield $model->newInstance($result, $condition)->setQuery($query);
            } else {
                yield $result;
            }
        }
    }

    /**
     * ִ�в�ѯ �������ݼ�
     * @access public
     * @param Query  $query ��ѯ����
     * @param string $sql   sqlָ��
     * @param array  $bind  ������
     * @param bool   $cache �Ƿ�֧�ֻ���
     * @return array
     * @throws BindParamException
     * @throws \PDOException
     * @throws \Exception
     * @throws \Throwable
     */
    public function query(Query $query, string $sql, array $bind = [], bool $cache = false): array
    {
        // ������ѯ���ʽ
        $options = $query->parseOptions();

        if ($cache && !empty($options['cache'])) {
            $cacheItem = $this->parseCache($query, $options['cache']);
            $resultSet = $this->cache->get($cacheItem->getKey());

            if (false !== $resultSet) {
                return $resultSet;
            }
        }

        $master    = !empty($options['master']) ? true : false;
        $procedure = !empty($options['procedure']) ? true : in_array(strtolower(substr(trim($sql), 0, 4)), ['call', 'exec']);

        $this->getPDOStatement($sql, $bind, $master, $procedure);

        $resultSet = $this->getResult($procedure);

        if (isset($cacheItem) && $resultSet) {
            // �������ݼ�
            $cacheItem->set($resultSet);
            $this->cacheData($cacheItem);
        }

        return $resultSet;
    }

    /**
     * ִ�в�ѯ��ֻ����PDOStatement����
     * @access public
     * @param Query $query ��ѯ����
     * @return \PDOStatement
     */
    public function pdo(Query $query): PDOStatement
    {
        $bind = $query->getBind();
        // ���ɲ�ѯSQL
        $sql = $this->builder->select($query);

        return $this->queryPDOStatement($query, $sql, $bind);
    }

    /**
     * ִ�в�ѯ��ֻ����PDOStatement����
     * @access public
     * @param string $sql       sqlָ��
     * @param array  $bind      ������
     * @param bool   $master    �Ƿ�����������������
     * @param bool   $procedure �Ƿ�Ϊ�洢���̵���
     * @return PDOStatement
     * @throws BindParamException
     * @throws \PDOException
     * @throws \Exception
     * @throws \Throwable
     */
    public function getPDOStatement(string $sql, array $bind = [], bool $master = false, bool $procedure = false): PDOStatement
    {
        $this->initConnect($this->readMaster ?: $master);

        // ��¼SQL���
        $this->queryStr = $sql;

        $this->bind = $bind;

        $this->db->updateQueryTimes();

        try {
            // ���Կ�ʼ
            $this->debug(true);

            // Ԥ����
            $this->PDOStatement = $this->linkID->prepare($sql);

            // ������
            if ($procedure) {
                $this->bindParam($bind);
            } else {
                $this->bindValue($bind);
            }

            // ִ�в�ѯ
            $this->PDOStatement->execute();

            // ���Խ���
            $this->debug(false, '', $master);

            return $this->PDOStatement;
        } catch (\Throwable | \Exception $e) {
            if ($this->isBreak($e)) {
                return $this->close()->getPDOStatement($sql, $bind, $master, $procedure);
            }

            if ($e instanceof \PDOException) {
                throw new PDOException($e, $this->config, $this->getLastsql());
            } else {
                throw $e;
            }
        }
    }

    /**
     * ִ�����
     * @access public
     * @param Query  $query  ��ѯ����
     * @param string $sql    sqlָ��
     * @param array  $bind   ������
     * @param bool   $origin �Ƿ�ԭ����ѯ
     * @return int
     * @throws BindParamException
     * @throws \PDOException
     * @throws \Exception
     * @throws \Throwable
     */
    public function execute(Query $query, string $sql, array $bind = [], bool $origin = false): int
    {
        $this->queryPDOStatement($query->master(true), $sql, $bind);

        if (!$origin && !empty($this->config['deploy']) && !empty($this->config['read_master'])) {
            $this->readMaster = true;
        }

        $this->numRows = $this->PDOStatement->rowCount();

        return $this->numRows;
    }

    protected function queryPDOStatement(Query $query, string $sql, array $bind = []): PDOStatement
    {
        $options   = $query->getOptions();
        $master    = !empty($options['master']) ? true : false;
        $procedure = !empty($options['procedure']) ? true : in_array(strtolower(substr(trim($sql), 0, 4)), ['call', 'exec']);

        return $this->getPDOStatement($sql, $bind, $master, $procedure);
    }

    /**
     * ���ҵ�����¼
     * @access public
     * @param Query $query ��ѯ����
     * @return array
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function find(Query $query): array
    {
        // ������ѯ���ʽ
        $options = $query->parseOptions();

        if (!empty($options['cache'])) {
            // �жϲ�ѯ����
            $cacheItem = $this->parseCache($query, $options['cache']);
            $key       = $cacheItem->getKey();
        }

        if (isset($key)) {
            $result = $this->cache->get($key);

            if (false !== $result) {
                return $result;
            }
        }

        // ���ɲ�ѯSQL
        $sql = $this->builder->select($query, true);

        // �¼��ص�
        $result = $this->db->trigger('before_find', $query);

        if (!$result) {
            // ִ�в�ѯ
            $resultSet = $this->query($query, $sql, $query->getBind());

            $result = $resultSet[0] ?? [];
        }

        if (isset($cacheItem) && $result) {
            // ��������
            $cacheItem->set($result);
            $this->cacheData($cacheItem);
        }

        return $result;
    }

    /**
     * ʹ���α��ѯ��¼
     * @access public
     * @param Query $query ��ѯ����
     * @return \Generator
     */
    public function cursor(Query $query)
    {
        // ������ѯ���ʽ
        $options = $query->parseOptions();

        // ���ɲ�ѯSQL
        $sql = $this->builder->select($query);

        $condition = $options['where']['AND'] ?? null;

        // ִ�в�ѯ����
        return $this->getCursor($query, $sql, $query->getBind(), $query->getModel(), $condition);
    }

    /**
     * ���Ҽ�¼
     * @access public
     * @param Query $query ��ѯ����
     * @return array
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function select(Query $query): array
    {
        // ������ѯ���ʽ
        $options = $query->parseOptions();

        if (!empty($options['cache'])) {
            $cacheItem = $this->parseCache($query, $options['cache']);
            $resultSet = $this->getCacheData($cacheItem);

            if (false !== $resultSet) {
                return $resultSet;
            }
        }

        // ���ɲ�ѯSQL
        $sql = $this->builder->select($query);

        $resultSet = $this->db->trigger('before_select', $query);

        if (!$resultSet) {
            // ִ�в�ѯ����
            $resultSet = $this->query($query, $sql, $query->getBind());
        }

        if (isset($cacheItem) && false !== $resultSet) {
            // �������ݼ�
            $cacheItem->set($resultSet);
            $this->cacheData($cacheItem);
        }

        return $resultSet;
    }

    /**
     * �����¼
     * @access public
     * @param Query   $query        ��ѯ����
     * @param boolean $getLastInsID ������������
     * @return mixed
     */
    public function insert(Query $query, bool $getLastInsID = false)
    {
        // ������ѯ���ʽ
        $options = $query->parseOptions();

        // ����SQL���
        $sql = $this->builder->insert($query);

        // ִ�в���
        $result = '' == $sql ? 0 : $this->execute($query, $sql, $query->getBind());

        if ($result) {
            $sequence  = $options['sequence'] ?? null;
            $lastInsId = $query->getLastInsID($sequence);

            $data = $options['data'];

            if ($lastInsId) {
                $pk = $query->getPk();
                if (is_string($pk)) {
                    $data[$pk] = $lastInsId;
                }
            }

            $query->setOption('data', $data);

            $this->db->trigger('after_insert', $query);

            if ($getLastInsID) {
                return $lastInsId;
            }
        }

        return $result;
    }

    /**
     * ���������¼
     * @access public
     * @param Query   $query   ��ѯ����
     * @param mixed   $dataSet ���ݼ�
     * @param integer $limit   ÿ��д����������
     * @return integer
     * @throws \Exception
     * @throws \Throwable
     */
    public function insertAll(Query $query, array $dataSet = [], int $limit = 0): int
    {
        if (!is_array(reset($dataSet))) {
            return 0;
        }

        $query->parseOptions();

        if ($limit) {
            // ����д�� �Զ���������֧��
            $this->startTrans();

            try {
                $array = array_chunk($dataSet, $limit, true);
                $count = 0;

                foreach ($array as $item) {
                    $sql = $this->builder->insertAll($query, $item);
                    $count += $this->execute($query, $sql, $query->getBind());
                }

                // �ύ����
                $this->commit();
            } catch (\Exception | \Throwable $e) {
                $this->rollback();
                throw $e;
            }

            return $count;
        }

        $sql = $this->builder->insertAll($query, $dataSet);

        return $this->execute($query, $sql, $query->getBind());
    }

    /**
     * ͨ��Select��ʽ�����¼
     * @access public
     * @param Query  $query  ��ѯ����
     * @param array  $fields Ҫ��������ݱ��ֶ���
     * @param string $table  Ҫ��������ݱ���
     * @return integer
     * @throws PDOException
     */
    public function selectInsert(Query $query, array $fields, string $table): int
    {
        // ������ѯ���ʽ
        $query->parseOptions();

        $sql = $this->builder->selectInsert($query, $fields, $table);

        return $this->execute($query, $sql, $query->getBind());
    }

    /**
     * ���¼�¼
     * @access public
     * @param Query $query ��ѯ����
     * @return integer
     * @throws Exception
     * @throws PDOException
     */
    public function update(Query $query): int
    {
        $options = $query->parseOptions();

        if (isset($options['cache'])) {
            $cacheItem = $this->parseCache($query, $options['cache']);
            $key       = $cacheItem->getKey();
            $tag       = $cacheItem->getTag();
        }

        // ����UPDATE SQL���
        $sql = $this->builder->update($query);

        // ��⻺��
        if (isset($key) && $this->cache->get($key)) {
            // ɾ������
            $this->cache->delete($key);
        } elseif (!empty($tag)) {
            $this->cache->tag($tag)->clear();
        }

        // ִ�в���
        $result = '' == $sql ? 0 : $this->execute($query, $sql, $query->getBind());

        if ($result) {
            $this->db->trigger('after_update', $query);
        }

        return $result;
    }

    /**
     * ɾ����¼
     * @access public
     * @param Query $query ��ѯ����
     * @return int
     * @throws Exception
     * @throws PDOException
     */
    public function delete(Query $query): int
    {
        // ������ѯ���ʽ
        $options = $query->parseOptions();

        if (isset($options['cache'])) {
            $cacheItem = $this->parseCache($query, $options['cache']);
            $key       = $cacheItem->getKey();
            $tag       = $cacheItem->getTag();
        }

        // ����ɾ��SQL���
        $sql = $this->builder->delete($query);

        // ��⻺��
        if (isset($key) && $this->cache->get($key)) {
            // ɾ������
            $this->cache->delete($key);
        } elseif (!empty($tag)) {
            $this->cache->tag($tag)->clear();
        }

        // ִ�в���
        $result = $this->execute($query, $sql, $query->getBind());

        if ($result) {
            $this->db->trigger('after_delete', $query);
        }

        return $result;
    }

    /**
     * �õ�ĳ���ֶε�ֵ
     * @access public
     * @param Query  $query   ��ѯ����
     * @param string $field   �ֶ���
     * @param mixed  $default Ĭ��ֵ
     * @param bool   $one     ����һ��ֵ
     * @return mixed
     */
    public function value(Query $query, string $field, $default = null, bool $one = true)
    {
        $options = $query->parseOptions();

        if (isset($options['field'])) {
            $query->removeOption('field');
        }

        $query->setOption('field', (array) $field);

        if (!empty($options['cache'])) {
            $cacheItem = $this->parseCache($query, $options['cache']);
            $result    = $this->getCacheData($cacheItem);

            if (false !== $result) {
                return $result;
            }
        }

        // ���ɲ�ѯSQL
        $sql = $this->builder->select($query, $one);

        if (isset($options['field'])) {
            $query->setOption('field', $options['field']);
        } else {
            $query->removeOption('field');
        }

        // ִ�в�ѯ����
        $pdo = $this->getPDOStatement($sql, $query->getBind(), $options['master']);

        $result = $pdo->fetchColumn();

        if (isset($cacheItem) && false !== $result) {
            // ��������
            $cacheItem->set($result);
            $this->cacheData($cacheItem);
        }

        return false !== $result ? $result : $default;
    }

    /**
     * �õ�ĳ���ֶε�ֵ
     * @access public
     * @param Query  $query     ��ѯ����
     * @param string $aggregate �ۺϷ���
     * @param mixed  $field     �ֶ���
     * @param bool   $force     ǿ��תΪ��������
     * @return mixed
     */
    public function aggregate(Query $query, string $aggregate, $field, bool $force = false)
    {
        if (is_string($field) && 0 === stripos($field, 'DISTINCT ')) {
            list($distinct, $field) = explode(' ', $field);
        }

        $field = $aggregate . '(' . (!empty($distinct) ? 'DISTINCT ' : '') . $this->builder->parseKey($query, $field, true) . ') AS tp_' . strtolower($aggregate);

        $result = $this->value($query, $field, 0, false);

        return $force ? (float) $result : $result;
    }

    /**
     * �õ�ĳ���е�����
     * @access public
     * @param Query  $query  ��ѯ����
     * @param string $column �ֶ��� ����ֶ��ö��ŷָ�
     * @param string $key    ����
     * @return array
     */
    public function column(Query $query, string $column, string $key = ''): array
    {
        $options = $query->parseOptions();

        if (isset($options['field'])) {
            $query->removeOption('field');
        }

        if ($key && '*' != $column) {
            $field = $key . ',' . $column;
        } else {
            $field = $column;
        }

        $field = array_map('trim', explode(',', $field));

        $query->setOption('field', $field);

        if (!empty($options['cache'])) {
            // �жϲ�ѯ����
            $cacheItem = $this->parseCache($query, $options['cache']);
            $result    = $this->getCacheData($cacheItem);

            if (false !== $result) {
                return $result;
            }
        }

        // ���ɲ�ѯSQL
        $sql = $this->builder->select($query);

        if (isset($options['field'])) {
            $query->setOption('field', $options['field']);
        } else {
            $query->removeOption('field');
        }

        // ִ�в�ѯ����
        $pdo = $this->getPDOStatement($sql, $query->getBind(), $options['master']);

        $resultSet = $pdo->fetchAll(PDO::FETCH_ASSOC);

        if (empty($resultSet)) {
            $result = [];
        } elseif (('*' == $column || strpos($column, ',')) && $key) {
            $result = array_column($resultSet, null, $key);
        } else {
            $fields = array_keys($resultSet[0]);
            $key    = $key ?: array_shift($fields);

            if (strpos($key, '.')) {
                list($alias, $key) = explode('.', $key);
            }

            $result = array_column($resultSet, $column, $key);
        }

        if (isset($cacheItem)) {
            // ��������
            $cacheItem->set($result);
            $this->cacheData($cacheItem);
        }

        return $result;
    }

    /**
     * ���ݲ�������װ���յ�SQL��� ���ڵ���
     * @access public
     * @param string $sql  �������󶨵�sql���
     * @param array  $bind �������б�
     * @return string
     */
    public function getRealSql(string $sql, array $bind = []): string
    {
        foreach ($bind as $key => $val) {
            $value = is_array($val) ? $val[0] : $val;
            $type  = is_array($val) ? $val[1] : PDO::PARAM_STR;

            if ((self::PARAM_FLOAT == $type || PDO::PARAM_STR == $type) && is_string($value)) {
                $value = '\'' . addslashes($value) . '\'';
            } elseif (PDO::PARAM_INT == $type && '' === $value) {
                $value = 0;
            }

            // �ж�ռλ��
            $sql = is_numeric($key) ?
            substr_replace($sql, $value, strpos($sql, '?'), 1) :
            substr_replace($sql, $value, strpos($sql, ':' . $key), strlen(':' . $key));
        }

        return rtrim($sql);
    }

    /**
     * ������
     * ֧�� ['name'=>'value','id'=>123] ��Ӧ����ռλ��
     * ���� ['value',123] ��Ӧ�ʺ�ռλ��
     * @access public
     * @param array $bind Ҫ�󶨵Ĳ����б�
     * @return void
     * @throws BindParamException
     */
    protected function bindValue(array $bind = []): void
    {
        foreach ($bind as $key => $val) {
            // ռλ��
            $param = is_numeric($key) ? $key + 1 : ':' . $key;

            if (is_array($val)) {
                if (PDO::PARAM_INT == $val[1] && '' === $val[0]) {
                    $val[0] = 0;
                } elseif (self::PARAM_FLOAT == $val[1]) {
                    $val[0] = is_string($val[0]) ? (float) $val[0] : $val[0];
                    $val[1] = PDO::PARAM_STR;
                }

                $result = $this->PDOStatement->bindValue($param, $val[0], $val[1]);
            } else {
                $result = $this->PDOStatement->bindValue($param, $val);
            }

            if (!$result) {
                throw new BindParamException(
                    "Error occurred  when binding parameters '{$param}'",
                    $this->config,
                    $this->getLastsql(),
                    $bind
                );
            }
        }
    }

    /**
     * �洢���̵��������������
     * @access public
     * @param array $bind Ҫ�󶨵Ĳ����б�
     * @return void
     * @throws BindParamException
     */
    protected function bindParam(array $bind): void
    {
        foreach ($bind as $key => $val) {
            $param = is_numeric($key) ? $key + 1 : ':' . $key;

            if (is_array($val)) {
                array_unshift($val, $param);
                $result = call_user_func_array([$this->PDOStatement, 'bindParam'], $val);
            } else {
                $result = $this->PDOStatement->bindValue($param, $val);
            }

            if (!$result) {
                $param = array_shift($val);

                throw new BindParamException(
                    "Error occurred  when binding parameters '{$param}'",
                    $this->config,
                    $this->getLastsql(),
                    $bind
                );
            }
        }
    }

    /**
     * ������ݼ�����
     * @access protected
     * @param bool $procedure �Ƿ�洢����
     * @return array
     */
    protected function getResult(bool $procedure = false): array
    {
        if ($procedure) {
            // �洢���̷��ؽ��
            return $this->procedure();
        }

        $result = $this->PDOStatement->fetchAll($this->fetchType);

        $this->numRows = count($result);

        return $result;
    }

    /**
     * ��ô洢�������ݼ�
     * @access protected
     * @return array
     */
    protected function procedure(): array
    {
        $item = [];

        do {
            $result = $this->getResult();
            if (!empty($result)) {
                $item[] = $result;
            }
        } while ($this->PDOStatement->nextRowset());

        $this->numRows = count($item);

        return $item;
    }

    /**
     * ִ�����ݿ�����
     * @access public
     * @param callable $callback ���ݲ��������ص�
     * @return mixed
     * @throws PDOException
     * @throws \Exception
     * @throws \Throwable
     */
    public function transaction(callable $callback)
    {
        $this->startTrans();

        try {
            $result = null;
            if (is_callable($callback)) {
                $result = call_user_func_array($callback, [$this]);
            }

            $this->commit();
            return $result;
        } catch (\Exception | \Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * ��������
     * @access public
     * @return void
     * @throws \PDOException
     * @throws \Exception
     */
    public function startTrans(): void
    {
        $this->initConnect(true);

        ++$this->transTimes;

        try {
            if (1 == $this->transTimes) {
                $this->linkID->beginTransaction();
            } elseif ($this->transTimes > 1 && $this->supportSavepoint()) {
                $this->linkID->exec(
                    $this->parseSavepoint('trans' . $this->transTimes)
                );
            }
        } catch (\Exception $e) {
            if ($this->isBreak($e)) {
                --$this->transTimes;
                $this->close()->startTrans();
            }
            throw $e;
        }
    }

    /**
     * ���ڷ��Զ��ύ״̬����Ĳ�ѯ�ύ
     * @access public
     * @return void
     * @throws PDOException
     */
    public function commit(): void
    {
        $this->initConnect(true);

        if (1 == $this->transTimes) {
            $this->linkID->commit();
        }

        --$this->transTimes;
    }

    /**
     * ����ع�
     * @access public
     * @return void
     * @throws PDOException
     */
    public function rollback(): void
    {
        $this->initConnect(true);

        if (1 == $this->transTimes) {
            $this->linkID->rollBack();
        } elseif ($this->transTimes > 1 && $this->supportSavepoint()) {
            $this->linkID->exec(
                $this->parseSavepointRollBack('trans' . $this->transTimes)
            );
        }

        $this->transTimes = max(0, $this->transTimes - 1);
    }

    /**
     * �Ƿ�֧������Ƕ��
     * @return bool
     */
    protected function supportSavepoint(): bool
    {
        return false;
    }

    /**
     * ���ɶ��屣����SQL
     * @access protected
     * @param string $name ��ʶ
     * @return string
     */
    protected function parseSavepoint(string $name): string
    {
        return 'SAVEPOINT ' . $name;
    }

    /**
     * ���ɻع���������SQL
     * @access protected
     * @param string $name ��ʶ
     * @return string
     */
    protected function parseSavepointRollBack(string $name): string
    {
        return 'ROLLBACK TO SAVEPOINT ' . $name;
    }

    /**
     * ������ִ��SQL���
     * �������ָ���Ϊ��execute����
     * @access public
     * @param Query $query    ��ѯ����
     * @param array $sqlArray SQL������ָ��
     * @param array $bind     ������
     * @return bool
     */
    public function batchQuery(Query $query, array $sqlArray = [], array $bind = []): bool
    {
        // �Զ���������֧��
        $this->startTrans();

        try {
            foreach ($sqlArray as $sql) {
                $this->execute($query, $sql, $bind);
            }
            // �ύ����
            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }

        return true;
    }

    /**
     * �ر����ݿ⣨�����������ӣ�
     * @access public
     * @return $this
     */
    public function close()
    {
        $this->linkID    = null;
        $this->linkWrite = null;
        $this->linkRead  = null;
        $this->links     = [];

        $this->free();

        return $this;
    }

    /**
     * �Ƿ����
     * @access protected
     * @param \PDOException|\Exception $e �쳣����
     * @return bool
     */
    protected function isBreak($e): bool
    {
        if (!$this->config['break_reconnect']) {
            return false;
        }

        $error = $e->getMessage();

        foreach ($this->breakMatchStr as $msg) {
            if (false !== stripos($error, $msg)) {
                return true;
            }
        }

        return false;
    }

    /**
     * ��ȡ���һ�β�ѯ��sql���
     * @access public
     * @return string
     */
    public function getLastSql(): string
    {
        return $this->getRealSql($this->queryStr, $this->bind);
    }

    /**
     * ��ȡ��������ID
     * @access public
     * @param string $sequence ����������
     * @return string
     */
    public function getLastInsID(string $sequence = null): string
    {
        return $this->linkID->lastInsertId($sequence);
    }

    /**
     * ��ȡ���ػ���Ӱ��ļ�¼��
     * @access public
     * @return integer
     */
    public function getNumRows(): int
    {
        return $this->numRows;
    }

    /**
     * ��ȡ����Ĵ�����Ϣ
     * @access public
     * @return string
     */
    public function getError(): string
    {
        if ($this->PDOStatement) {
            $error = $this->PDOStatement->errorInfo();
            $error = $error[1] . ':' . $error[2];
        } else {
            $error = '';
        }

        if ('' != $this->queryStr) {
            $error .= "\n [ SQL��� ] : " . $this->getLastsql();
        }

        return $error;
    }

    /**
     * ���ݿ���� ��¼��ǰSQL����������
     * @access protected
     * @param boolean $start  ���Կ�ʼ��� true ��ʼ false ����
     * @param string  $sql    ִ�е�SQL��� �����Զ���ȡ
     * @param bool    $master ���ӱ��
     * @return void
     */
    protected function debug(bool $start, string $sql = '', bool $master = false): void
    {
        if (!empty($this->config['debug'])) {
            // �������ݿ����ģʽ
            if ($start) {
                $this->queryStartTime = microtime(true);
            } else {
                // ��¼��������ʱ��
                $runtime = number_format((microtime(true) - $this->queryStartTime), 6);
                $sql     = $sql ?: $this->getLastsql();
                $result  = [];

                // SQL���ܷ���
                if ($this->config['sql_explain'] && 0 === stripos(trim($sql), 'select')) {
                    $result = $this->getExplain($sql);
                }

                // SQL����
                $this->triggerSql($sql, $runtime, $result, $master);
            }
        }
    }

    /**
     * ����SQL�¼�
     * @access protected
     * @param string $sql     SQL���
     * @param string $runtime SQL����ʱ��
     * @param mixed  $explain SQL����
     * @param bool   $master  ���ӱ��
     * @return void
     */
    protected function triggerSql(string $sql, string $runtime, array $explain = [], bool $master = false): void
    {
        $listen = $this->db->getListen();
        if (!empty($listen)) {
            foreach ($listen as $callback) {
                if (is_callable($callback)) {
                    $callback($sql, $runtime, $explain, $master);
                }
            }
        } else {
            if ($this->config['deploy']) {
                // �ֲ�ʽ��¼��ǰ����������
                $master = $master ? 'master|' : 'slave|';
            } else {
                $master = '';
            }

            // δע��������¼����־��
            $this->log('[ SQL ] ' . $sql . ' [ ' . $master . 'RunTime:' . $runtime . 's ]');

            if (!empty($explain)) {
                $this->log('[ EXPLAIN : ' . var_export($explain, true) . ' ]');
            }
        }
    }

    /**
     * ��¼SQL��־
     * @access protected
     * @param string $log  SQL��־��Ϣ
     * @param string $type ��־����
     * @return void
     */
    protected function log($log, $type = 'sql'): void
    {
        if ($this->config['debug']) {
            $this->log->record($log, $type);
        }
    }

    /**
     * ��ʼ�����ݿ�����
     * @access protected
     * @param boolean $master �Ƿ���������
     * @return void
     */
    protected function initConnect(bool $master = true): void
    {
        if (!empty($this->config['deploy'])) {
            // ���÷ֲ�ʽ���ݿ�
            if ($master || $this->transTimes) {
                if (!$this->linkWrite) {
                    $this->linkWrite = $this->multiConnect(true);
                }

                $this->linkID = $this->linkWrite;
            } else {
                if (!$this->linkRead) {
                    $this->linkRead = $this->multiConnect(false);
                }

                $this->linkID = $this->linkRead;
            }
        } elseif (!$this->linkID) {
            // Ĭ�ϵ����ݿ�
            $this->linkID = $this->connect();
        }
    }

    /**
     * ���ӷֲ�ʽ������
     * @access protected
     * @param boolean $master ��������
     * @return PDO
     */
    protected function multiConnect(bool $master = false): PDO
    {
        $config = [];

        // �ֲ�ʽ���ݿ����ý���
        foreach (['username', 'password', 'hostname', 'hostport', 'database', 'dsn', 'charset'] as $name) {
            $config[$name] = is_string($this->config[$name]) ? explode(',', $this->config[$name]) : $this->config[$name];
        }

        // �����������
        $m = floor(mt_rand(0, $this->config['master_num'] - 1));

        if ($this->config['rw_separate']) {
            // ����ʽ���ö�д����
            if ($master) // ��������д��
            {
                $r = $m;
            } elseif (is_numeric($this->config['slave_no'])) {
                // ָ����������
                $r = $this->config['slave_no'];
            } else {
                // ���������Ӵӷ����� ÿ��������ӵ����ݿ�
                $r = floor(mt_rand($this->config['master_num'], count($config['hostname']) - 1));
            }
        } else {
            // ��д���������ַ����� ÿ��������ӵ����ݿ�
            $r = floor(mt_rand(0, count($config['hostname']) - 1));
        }
        $dbMaster = false;

        if ($m != $r) {
            $dbMaster = [];
            foreach (['username', 'password', 'hostname', 'hostport', 'database', 'dsn', 'charset'] as $name) {
                $dbMaster[$name] = $config[$name][$m] ?? $config[$name][0];
            }
        }

        $dbConfig = [];

        foreach (['username', 'password', 'hostname', 'hostport', 'database', 'dsn', 'charset'] as $name) {
            $dbConfig[$name] = $config[$name][$r] ?? $config[$name][0];
        }

        return $this->connect($dbConfig, $r, $r == $m ? false : $dbMaster);
    }

    /**
     * ��������
     * @access public
     */
    public function __destruct()
    {
        // �ͷŲ�ѯ
        $this->free();

        // �ر�����
        $this->close();
    }

    /**
     * ��������
     * @access protected
     * @param CacheItem $cacheItem ����Item
     */
    protected function cacheData(CacheItem $cacheItem): void
    {
        if ($cacheItem->getTag()) {
            $this->cache->tag($cacheItem->getTag());
        }

        $this->cache->set($cacheItem->getKey(), $cacheItem->get(), $cacheItem->getExpire());
    }

    /**
     * ��ȡ��������
     * @access protected
     * @param CacheItem $cacheItem
     * @return mixed
     */
    protected function getCacheData(CacheItem $cacheItem)
    {
        // �жϲ�ѯ����
        return $this->cache->get($cacheItem->getKey());
    }

    protected function parseCache(Query $query, array $cache): CacheItem
    {
        list($key, $expire, $tag) = $cache;

        if ($key instanceof CacheItem) {
            $cacheItem = $key;
        } else {
            if (true === $key) {
                if (!empty($query->getOptions('key'))) {
                    $key = 'think:' . $this->getConfig('database') . '.' . $query->getTable() . '|' . $query->getOptions('key');
                } else {
                    $key = md5($this->getConfig('database') . serialize($query->getOptions()) . serialize($query->getBind(false)));
                }
            }

            $cacheItem = new CacheItem($key);
            $cacheItem->expire($expire);
            $cacheItem->tag($tag);
        }

        return $cacheItem;
    }

    /**
     * ��ʱ���¼�� ����false��ʾ��Ҫ��ʱ
     * ���򷵻�ʵ��д�����ֵ
     * @access public
     * @param string  $type     ���������Լ�
     * @param string  $guid     д���ʶ
     * @param float   $step     д�벽��ֵ
     * @param integer $lazyTime ��ʱʱ��(s)
     * @return false|integer
     */
    public function lazyWrite(string $type, string $guid, float $step, int $lazyTime)
    {
        if (!$this->cache->has($guid . '_time')) {
            // ��ʱ��ʼ
            $this->cache->set($guid . '_time', time(), 0);
            $this->cache->$type($guid, $step);
        } elseif (time() > $this->cache->get($guid . '_time') + $lazyTime) {
            // ɾ������
            $value = $this->cache->$type($guid, $step);
            $this->cache->delete($guid);
            $this->cache->delete($guid . '_time');
            return 0 === $value ? false : $value;
        } else {
            // ���»���
            $this->cache->$type($guid, $step);
        }

        return false;
    }
}
