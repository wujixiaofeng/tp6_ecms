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

/**
 * Session������
 */
class Session
{
    /**
     * ���ò���
     * @var array
     */
    protected $config = [];

    /**
     * Session����
     * @var array
     */
    protected $data = [];

    /**
     * �Ƿ��ʼ��
     * @var bool
     */
    protected $init = null;

    /**
     * ��¼Session name
     * @var string
     */
    protected $sessionName = 'PHPSESSID';

    /**
     * ��¼Session Id
     * @var string
     */
    protected $sessionId;

    /**
     * Session��Ч��
     * @var int
     */
    protected $expire = 0;

    /**
     * Requestʵ��
     * @var Request
     */
    protected $request;

    /**
     * Sessionд�����
     * @var object
     */
    protected $handler;

    public function __construct(Request $request, array $config = [])
    {
        $this->config  = $config;
        $this->request = $request;
    }

    public static function __make(Request $request, Config $config)
    {
        return new static($request, $config->get('session'));
    }

    /**
     * ����
     * @access public
     * @param  array $config
     * @return void
     */
    public function setConfig(array $config = []): void
    {
        $this->config = array_merge($this->config, array_change_key_case($config));
    }

    /**
     * ��������
     * @access public
     * @param  array $data
     * @return void
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * session��ʼ��
     * @access public
     * @return void
     * @throws \think\Exception
     */
    public function init(): void
    {
        if (!empty($this->config['name'])) {
            $this->sessionName = $this->config['name'];
        }

        if (!empty($this->config['expire'])) {
            $this->expire = $this->config['expire'];
        }

        // ��ʼ��sessionд������
        $type = !empty($this->config['type']) ? $this->config['type'] : 'File';

        $this->handler = App::factory($type, '\\think\\session\\driver\\', $this->config);

        $this->start();
    }

    /**
     * ����SessionName
     * @access public
     * @param  string $name session_name
     * @return void
     */
    public function setName(string $name): void
    {
        $this->sessionName = $name;
    }

    /**
     * ��ȡsessionName
     * @access public
     * @return string
     */
    public function getName(): string
    {
        return $this->sessionName;
    }

    /**
     * session_id����
     * @access public
     * @param  string $id session_id
     * @return void
     */
    public function setId(string $id): void
    {
        $this->sessionId = $id;
    }

    /**
     * ��ȡsession_id
     * @access public
     * @param  bool $regenerate �������Ƿ��Զ�����
     * @return string
     */
    public function getId(bool $regenerate = true): string
    {
        if ($this->sessionId) {
            return $this->sessionId;
        }

        return $regenerate ? $this->regenerate() : '';
    }

    /**
     * session����
     * @access public
     * @param  string $name session����
     * @param  mixed  $value sessionֵ
     * @return void
     */
    public function set(string $name, $value): void
    {
        empty($this->init) && $this->init();

        if (strpos($name, '.')) {
            // ��ά���鸳ֵ
            list($name1, $name2) = explode('.', $name);

            $this->data[$name1][$name2] = $value;
        } else {
            $this->data[$name] = $value;
        }
    }

    /**
     * session��ȡ
     * @access public
     * @param  string $name session����
     * @param  mixed  $default Ĭ��ֵ
     * @return mixed
     */
    public function get(string $name = '', $default = null)
    {
        empty($this->init) && $this->init();

        $sessionId = $this->getId();

        return $this->readSession($name, $default);
    }

    /**
     * session��ȡ
     * @access protected
     * @param  string $name session����
     * @param  mixed  $default Ĭ��ֵ
     * @return mixed
     */
    protected function readSession(string $name = '', $default = null)
    {
        $value = $this->data;

        if ('' != $name) {
            $name = explode('.', $name);

            foreach ($name as $val) {
                if (isset($value[$val])) {
                    $value = $value[$val];
                } else {
                    $value = $default;
                    break;
                }
            }
        }

        return $value;
    }

    /**
     * ɾ��session����
     * @access public
     * @param  string $name session����
     * @return void
     */
    public function delete(string $name): bool
    {
        empty($this->init) && $this->init();

        $sessionId = $this->getId(false);

        if (!$sessionId) {
            return false;
        }

        if (strpos($name, '.')) {
            list($name1, $name2) = explode('.', $name);
            unset($this->data[$name1][$name2]);
        } else {
            unset($this->data[$name]);
        }

        return true;
    }

    /**
     * ����session����
     * @access public
     * @return void
     */
    public function save()
    {
        if ($this->handler) {
            $sessionId = $this->getId(false);

            if (!empty($this->data)) {
                $data = $this->serialize($this->data);

                $this->handler->write($sessionId, $data, $this->expire);
            } else {
                $this->handler->delete($sessionId);
            }
        }
    }

    /**
     * ���session����
     * @access public
     * @return void
     */
    public function clear(): void
    {
        empty($this->init) && $this->init();

        $sessionId = $this->getId(false);

        if ($sessionId) {
            $this->data = [];
        }
    }

    /**
     * �ж�session����
     * @access public
     * @param  string $name session����
     * @return bool
     */
    public function has(string $name): bool
    {
        empty($this->init) && $this->init();

        $sessionId = $this->getId(false);

        if ($sessionId) {
            return $this->hasSession($name);
        }

        return false;
    }

    /**
     * �ж�session����
     * @access protected
     * @param  string $name session����
     * @return bool
     */
    protected function hasSession(string $name): bool
    {
        $value = $this->data ?: [];

        $name = explode('.', $name);

        foreach ($name as $val) {
            if (!isset($value[$val])) {
                return false;
            } else {
                $value = $value[$val];
            }
        }

        return true;
    }

    /**
     * ����session
     * @access public
     * @return void
     */
    public function start(): void
    {
        $sessionId = $this->getId();

        // ��ȡ��������
        if (empty($this->data)) {
            $data = $this->handler->read($sessionId);

            if (!empty($data)) {
                $this->data = $this->unserialize($data);
            }
        }

        $this->init = true;
    }

    /**
     * ����session
     * @access public
     * @return void
     */
    public function destroy(): void
    {
        $sessionId = $this->getId(false);

        if ($sessionId && !empty($this->data)) {
            $this->data = [];
            $this->save();
        }
    }

    /**
     * ��������session_id
     * @access protected
     * @param  bool $delete �Ƿ�ɾ�������Ự�ļ�
     * @return string
     */
    protected function regenerate(bool $delete = false): string
    {
        if ($delete) {
            $data = $this->data;
            $this->destroy();
            $this->data = $data;
        }

        $sessionId = md5(microtime(true) . uniqid());

        $this->setId($sessionId);
        return $sessionId;
    }

    /**
     * session��ȡ��ɾ��
     * @access public
     * @param  string $name session����
     * @return mixed
     */
    public function pull(string $name)
    {
        $result = $this->get($name);

        if ($result) {
            $this->delete($name);
            return $result;
        }
    }

    /**
     * session���� ��һ��������Ч
     * @access public
     * @param  string $name session����
     * @param  mixed  $value sessionֵ
     * @return void
     */
    public function flash(string $name, $value): void
    {
        $this->set($name, $value);

        if (!$this->has('__flash__.__time__')) {
            $this->set('__flash__.__time__', $this->request->time(true));
        }

        $this->push('__flash__', $name);
    }

    /**
     * ��յ�ǰ�����session����
     * @access public
     * @return void
     */
    public function flush()
    {
        if (!$this->init) {
            return;
        }

        $items = $this->get('__flash__');

        if (!empty($items)) {
            $time = $items['__time__'];

            if ($this->request->time(true) > $time) {
                unset($items['__time__']);

                foreach ($items as $item) {
                    $this->delete($item);
                }

                $this->set('__flash__', []);
            }
        }
    }

    /**
     * ������ݵ�һ��session����
     * @access public
     * @param  string $key
     * @param  mixed  $value
     * @return void
     */
    public function push(string $key, $value): void
    {
        $array = $this->get($key);

        if (is_null($array)) {
            $array = [];
        }

        $array[] = $value;

        $this->set($key, $array);
    }

    /**
     * ���л�����
     * @access protected
     * @param  mixed $data
     * @return string
     */
    protected function serialize($data): string
    {
        $serialize = $this->config['serialize'][0] ?? 'serialize';

        return $serialize($data);
    }

    /**
     * �����л�����
     * @access protected
     * @param  string $data
     * @return array
     */
    protected function unserialize(string $data): array
    {
        $unserialize = $this->config['serialize'][1] ?? 'unserialize';

        return (array) $unserialize($data);
    }
}
