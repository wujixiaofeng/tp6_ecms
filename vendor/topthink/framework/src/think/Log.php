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

use Psr\Log\LoggerInterface;

/**
 * ��־������
 */
class Log implements LoggerInterface
{
    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';
    const SQL       = 'sql';

    /**
     * Ӧ�ö���
     * @var App
     */
    protected $app;

    /**
     * ��־��Ϣ
     * @var array
     */
    protected $log = [];

    /**
     * ���ò���
     * @var array
     */
    protected $config = [];

    /**
     * ��־д������
     * @var object
     */
    protected $driver;

    /**
     * ��־��Ȩkey
     * @var string
     */
    protected $key;

    /**
     * �Ƿ�������־д��
     * @var bool
     */
    protected $allowWrite = true;

    /**
     * ���췽��
     * @access public
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->init($app->config->get('log'));
    }

    /**
     * ��־��ʼ��
     * @access public
     * @param  array $config
     * @return $this
     */
    public function init(array $config = [])
    {
        $type = $config['type'] ?? 'File';

        $this->config = $config;

        unset($config['type']);
        if (!empty($config['close'])) {
            $this->allowWrite = false;
        }

        $this->driver = App::factory($type, '\\think\\log\\driver\\', $config);

        return $this;
    }

    /**
     * ��ȡ��־��Ϣ
     * @access public
     * @param  string $type ��Ϣ����
     * @return array
     */
    public function getLog(string $type = ''): array
    {
        return $type ? $this->log[$type] : $this->log;
    }

    /**
     * ��¼��־��Ϣ
     * @access public
     * @param  mixed  $msg       ��־��Ϣ
     * @param  string $type      ��־����
     * @param  array  $context   �滻����
     * @return $this
     */
    public function record($msg, string $type = 'info', array $context = [])
    {
        if (!$this->allowWrite) {
            return;
        }

        if (is_string($msg) && !empty($context)) {
            $replace = [];
            foreach ($context as $key => $val) {
                $replace['{' . $key . '}'] = $val;
            }

            $msg = strtr($msg, $replace);
        }

        if ($this->app->runningInConsole()) {
            if (empty($this->config['level']) || in_array($type, $this->config['level'])) {
                // ��������־ʵʱд��
                $this->write($msg, $type, true);
            }
        } else {
            $this->log[$type][] = $msg;
        }

        return $this;
    }

    /**
     * ��¼������־��Ϣ
     * @access public
     * @param  array  $msg       ��־��Ϣ
     * @param  string $type      ��־����
     * @return $this
     */
    public function append(array $log, string $type = 'info')
    {
        if (!$this->allowWrite || empty($log)) {
            return $this;
        }

        if (isset($this->log[$type])) {
            $this->log[$type] += $log;
        } else {
            $this->log[$type] = $log;
        }

        return $this;
    }

    /**
     * �����־��Ϣ
     * @access public
     * @return $this
     */
    public function clear()
    {
        $this->log = [];

        return $this;
    }

    /**
     * ��ǰ��־��¼����Ȩkey
     * @access public
     * @param  string  $key  ��Ȩkey
     * @return $this
     */
    public function key(string $key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * �����־д��Ȩ��
     * @access public
     * @param  array  $config  ��ǰ��־���ò���
     * @return bool
     */
    public function check(array $config): bool
    {
        if ($this->key && !empty($config['allow_key']) && !in_array($this->key, $config['allow_key'])) {
            return false;
        }

        return true;
    }

    /**
     * �رձ���������־д��
     * @access public
     * @return $this
     */
    public function close()
    {
        $this->allowWrite = false;
        $this->log        = [];

        return $this;
    }

    /**
     * ���������Ϣ
     * @access public
     * @return bool
     */
    public function save(): bool
    {
        if (empty($this->log) || !$this->allowWrite) {
            return true;
        }

        if (!$this->check($this->config)) {
            // �����־д��Ȩ��
            return false;
        }

        $log = [];

        foreach ($this->log as $level => $info) {
            if (!$this->app->isDebug() && 'debug' == $level) {
                continue;
            }

            if (empty($this->config['level']) || in_array($level, $this->config['level'])) {
                $log[$level] = $info;
                $this->app->event->trigger('LogLevel', [$level, $info]);
            }
        }

        $result = $this->driver->save($log);

        if ($result) {
            $this->log = [];
        }

        return $result;
    }

    /**
     * ʵʱд����־��Ϣ ��֧����Ϊ
     * @access public
     * @param  mixed  $msg   ������Ϣ
     * @param  string $type  ��־����
     * @param  bool   $force �Ƿ�ǿ��д��
     * @return bool
     */
    public function write($msg, string $type = 'info', bool $force = false): bool
    {
        // ��װ��־��Ϣ
        if (empty($this->config['level'])) {
            $force = true;
        }

        $log = [];

        if (true === $force || in_array($type, $this->config['level'])) {
            $log[$type][] = $msg;
        } else {
            return false;
        }

        // ����LogWrite
        $this->app->event->trigger('LogWrite', $log);

        // д����־
        return $this->driver->save($log, false);
    }

    /**
     * ��¼��־��Ϣ
     * @access public
     * @param  string $level     ��־����
     * @param  mixed  $message   ��־��Ϣ
     * @param  array  $context   �滻����
     * @return void
     */
    public function log($level, $message, array $context = []): void
    {
        $this->record($message, $level, $context);
    }

    /**
     * ��¼emergency��Ϣ
     * @access public
     * @param  mixed  $message   ��־��Ϣ
     * @param  array  $context   �滻����
     * @return void
     */
    public function emergency($message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * ��¼������Ϣ
     * @access public
     * @param  mixed  $message   ��־��Ϣ
     * @param  array  $context   �滻����
     * @return void
     */
    public function alert($message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * ��¼�������
     * @access public
     * @param  mixed  $message   ��־��Ϣ
     * @param  array  $context   �滻����
     * @return void
     */
    public function critical($message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * ��¼������Ϣ
     * @access public
     * @param  mixed  $message   ��־��Ϣ
     * @param  array  $context   �滻����
     * @return void
     */
    public function error($message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * ��¼warning��Ϣ
     * @access public
     * @param  mixed  $message   ��־��Ϣ
     * @param  array  $context   �滻����
     * @return void
     */
    public function warning($message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * ��¼notice��Ϣ
     * @access public
     * @param  mixed  $message   ��־��Ϣ
     * @param  array  $context   �滻����
     * @return void
     */
    public function notice($message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * ��¼һ����Ϣ
     * @access public
     * @param  mixed  $message   ��־��Ϣ
     * @param  array  $context   �滻����
     * @return void
     */
    public function info($message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * ��¼������Ϣ
     * @access public
     * @param  mixed  $message   ��־��Ϣ
     * @param  array  $context   �滻����
     * @return void
     */
    public function debug($message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * ��¼sql��Ϣ
     * @access public
     * @param  mixed  $message   ��־��Ϣ
     * @param  array  $context   �滻����
     * @return void
     */
    public function sql($message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }
}
