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
 * Session Memcache����
 */
class Memcache implements SessionHandlerInterface
{
    protected $handler = null;
    protected $config  = [
        'host'       => '127.0.0.1', // memcache����
        'port'       => 11211, // memcache�˿�
        'expire'     => 3600, // session��Ч��
        'timeout'    => 0, // ���ӳ�ʱʱ�䣨��λ�����룩
        'persistent' => true, // ������
        'prefix'     => '', // session name ��memcache keyǰ׺��
    ];

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);

        $this->init();
    }

    /**
     * ��Session
     * @access protected
     */
    protected function init(): bool
    {
        // ���php����
        if (!extension_loaded('memcache')) {
            throw new Exception('not support:memcache');
        }

        $this->handler = new \Memcache;

        // ֧�ּ�Ⱥ
        $hosts = (array) $this->config['host'];
        $ports = (array) $this->config['port'];

        if (empty($ports[0])) {
            $ports[0] = 11211;
        }

        // ��������
        foreach ($hosts as $i => $host) {
            $port = $ports[$i] ?? $ports[0];
            $this->config['timeout'] > 0 ?
            $this->handler->addServer($host, (int) $port, $this->config['persistent'], 1, $this->config['timeout']) :
            $this->handler->addServer($host, (int) $port, $this->config['persistent'], 1);
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
     * @param  string $data
     * @return array
     */
    public function write(string $sessID, string $data): bool
    {
        return $this->handler->set($this->config['prefix'] . $sessID, $data, 0, $this->config['expire']);
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
