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

use InvalidArgumentException;
use think\db\Connection;
use think\db\Query;
use think\db\Raw;
use think\exception\DbException;

/**
 * Class Db
 * @package think
 * @mixin Query
 */
class Db
{
    /**
     * ��ǰ���ݿ����Ӷ���
     * @var Connection
     */
    protected $connection;

    /**
     * ���ݿ�����ʵ��
     * @var array
     */
    protected $instance = [];

    /**
     * Event����
     * @var Event
     */
    protected $event;

    /**
     * ���ݿ�����
     * @var array
     */
    protected $config = [];

    /**
     * SQL����
     * @var array
     */
    protected $listen = [];

    /**
     * ��ѯ����
     * @var int
     */
    protected $queryTimes = 0;

    /**
     * �ܹ�����
     * @param array $config ��������
     * @access public
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * @param Event  $event
     * @param Config $config
     * @return Db
     * @codeCoverageIgnore
     */
    public static function __make(Event $event, Config $config)
    {
        $db = new static($config->get('database'));

        $db->setEvent($event);

        return $db;
    }

    /**
     * �л����ݿ�����
     * @access public
     * @param mixed       $config ��������
     * @param bool|string $name   ���ӱ�ʶ true ǿ����������
     * @return $this
     */
    public function connect($config = [], $name = false)
    {
        $this->connection = $this->instance($this->parseConfig($config), $name);
        return $this;
    }

    /**
     * ȡ�����ݿ�������ʵ��
     * @access public
     * @param array       $config ��������
     * @param bool|string $name   ���ӱ�ʶ true ǿ����������
     * @return Connection
     */
    public function instance(array $config = [], $name = false)
    {
        if (false === $name) {
            $name = md5(serialize($config));
        }

        if (true === $name || !isset($this->instance[$name])) {

            if (empty($config['type'])) {
                throw new InvalidArgumentException('Undefined db type');
            }

            if (true === $name) {
                $name = md5(serialize($config));
            }

            $this->instance[$name] = App::factory($config['type'], '\\think\\db\\connector\\', $config);
        }

        return $this->instance[$name];
    }

    /**
     * ʹ�ñ��ʽ��������
     * @access public
     * @param string $value ���ʽ
     * @return Raw
     */
    public function raw(string $value): Raw
    {
        return new Raw($value);
    }

    /**
     * ���²�ѯ����
     * @access public
     * @return void
     */
    public function updateQueryTimes(): void
    {
        $this->queryTimes++;
    }

    /**
     * ���ò�ѯ����
     * @access public
     * @return void
     */
    public function clearQueryTimes(): void
    {
        $this->queryTimes = 0;
    }

    /**
     * ��ò�ѯ����
     * @access public
     * @return integer
     */
    public function getQueryTimes(): int
    {
        return $this->queryTimes;
    }

    /**
     * ���ݿ����Ӳ�������
     * @access private
     * @param mixed $config
     * @return array
     */
    private function parseConfig($config): array
    {
        if (empty($config)) {
            $config = $this->config;
        } elseif (is_string($config) && isset($this->config[$config])) {
            // ֧�ֶ�ȡ���ò���
            $config = $this->config[$config];
        }

        if (!is_array($config)) {
            throw new DbException('database config error:' . $config);
        }

        return $config;
    }

    /**
     * ��ȡ���ݿ�����ò���
     * @access public
     * @param string $name ��������
     * @return mixed
     */
    public function getConfig(string $name = '')
    {
        return $name ? ($this->config[$name] ?? null) : $this->config;
    }

    /**
     * ����һ���µĲ�ѯ����
     * @access public
     * @param string|array $connection ����������Ϣ
     * @return mixed
     */
    public function buildQuery($connection = [])
    {
        $connection = $this->instance($this->parseConfig($connection));
        return $this->newQuery($connection);
    }

    /**
     * ����SQLִ��
     * @access public
     * @param callable $callback �ص�����
     * @return void
     */
    public function listen(callable $callback): void
    {
        $this->listen[] = $callback;
    }

    /**
     * ��ȡ����SQLִ��
     * @access public
     * @return array
     */
    public function getListen(): array
    {
        return $this->listen;
    }

    /**
     * ����Event����
     * @param Event $event
     */
    public function setEvent(Event $event)
    {
        $this->event = $event;
    }

    /**
     * ע��ص�����
     * @access public
     * @param string   $event    �¼���
     * @param callable $callback �ص�����
     * @return void
     */
    public function event(string $event, callable $callback): void
    {
        if ($this->event) {
            $this->event->listen('db.' . $event, $callback);
        }
    }

    /**
     * �����¼�
     * @access public
     * @param string $event  �¼���
     * @param mixed  $params �������
     * @param bool   $once
     * @return mixed
     */
    public function trigger(string $event, $params = null, bool $once = false)
    {
        if ($this->event) {
            return $this->event->trigger('db.' . $event, $params, $once);
        }
    }

    /**
     * ����һ���µĲ�ѯ����
     * @access protected
     * @param Connection $connection ���Ӷ���
     * @return mixed
     */
    protected function newQuery($connection = null)
    {
        /** @var Query $query */
        if (is_null($connection) && !$this->connection) {
            $this->connect($this->config);
        }

        $connection = $connection ?: $this->connection;
        $class      = $connection->getQueryClass();
        $query      = new $class($connection);

        $query->setDb($this);

        return $query;
    }

    public function __call($method, $args)
    {
        $query = $this->newQuery($this->connection);

        return call_user_func_array([$query, $method], $args);
    }
}
