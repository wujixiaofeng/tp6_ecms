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

namespace think\session\driver;

use think\contract\SessionHandlerInterface;
use think\Exception;

/**
 * Session Memcached����
 */
class Memcached implements SessionHandlerInterface
{
    protected $handler = null;
    protected $config  = [
        'host'     => '127.0.0.1', // memcache����
        'port'     => 11211, // memcache�˿�
        'expire'   => 3600, // session��Ч��
        'timeout'  => 0, // ���ӳ�ʱʱ�䣨��λ�����룩
        'prefix'   => '', // session name ��memcache keyǰ׺��
        'username' => '', //�˺�
        'password' => '', //����
    ];

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);

        $this->init();
    }

    /**
     * Session��ʼ��
     * @access protected
     * @return bool
     */
    protected function init(): bool
    {
        // ���php����
        if (!extension_loaded('memcached')) {
            throw new Exception('not support:memcached');
        }

        $this->handler = new \Memcached;

        // �������ӳ�ʱʱ�䣨��λ�����룩
        if ($this->config['timeout'] > 0) {
            $this->handler->setOption(\Memcached::OPT_CONNECT_TIMEOUT, $this->config['timeout']);
        }

        // ֧�ּ�Ⱥ
        $hosts = (array) $this->config['host'];
        $ports = (array) $this->config['port'];

        if (empty($ports[0])) {
            $ports[0] = 11211;
        }

        // ��������
        $servers = [];
        foreach ($hosts as $i => $host) {
            $servers[] = [$host, $ports[$i] ?? $ports[0], 1];
        }

        $this->handler->addServers($servers);

        if ('' != $this->config['username']) {
            $this->handler->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
            $this->handler->setSaslAuthData($this->config['username'], $this->config['password']);
        }

        return true;
    }

    /**
     * ��ȡSession
     * @access public
     * @param  string $sessID
     * @return string
     */
    public function read(string $sessID): string
    {
        return (string) $this->handler->get($this->config['prefix'] . $sessID);
    }

    /**
     * д��Session
     * @access public
     * @param  string $sessID
     * @param  array  $data
     * @return bool
     */
    public function write(string $sessID, string $data): bool
    {
        return $this->handler->set($this->config['prefix'] . $sessID, $data, $this->config['expire']);
    }

    /**
     * ɾ��Session
     * @access public
     * @param  string $sessID
     * @return bool
     */
    public function delete(string $sessID): bool
    {
        return $this->handler->delete($this->config['prefix'] . $sessID);
    }

}
