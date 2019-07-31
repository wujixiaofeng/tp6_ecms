<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace think\log\driver;

use think\App;
use think\contract\LogHandlerInterface;

/**
 * ���ػ�����������ļ�
 */
class File implements LogHandlerInterface
{
    /**
     * ���ò���
     * @var array
     */
    protected $config = [
        'time_format' => 'c',
        'single'      => false,
        'file_size'   => 2097152,
        'path'        => '',
        'apart_level' => [],
        'max_files'   => 0,
        'json'        => false,
    ];

    /**
     * Ӧ�ö���
     * @var App
     */
    protected $app;

    /**
     * �Ƿ����ִ̨��
     * @var bool
     */
    protected $isCli = false;

    // ʵ�������������
    public function __construct(App $app, $config = [])
    {
        $this->app = $app;

        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        }

        $this->isCli = $app->runningInConsole();
    }

    /**
     * ��־д��ӿ�
     * @access public
     * @param  array $log    ��־��Ϣ
     * @param  bool  $append �Ƿ�׷��������Ϣ
     * @return bool
     */
    public function save(array $log, bool $append = false): bool
    {
        $destination = $this->getMasterLogFile();

        $path = dirname($destination);
        !is_dir($path) && mkdir($path, 0755, true);

        $info = [];

        foreach ($log as $type => $val) {

            foreach ($val as $msg) {
                if (!is_string($msg)) {
                    $msg = var_export($msg, true);
                }

                $info[$type][] = $this->config['json'] ? $msg : '[ ' . $type . ' ] ' . $msg;
            }

            if (!$this->config['json'] && (true === $this->config['apart_level'] || in_array($type, $this->config['apart_level']))) {
                // ������¼����־����
                $filename = $this->getApartLevelFile($path, $type);

                $this->write($info[$type], $filename, true, $append);

                unset($info[$type]);
            }
        }

        if ($info) {
            return $this->write($info, $destination, false, $append);
        }

        return true;
    }

    /**
     * ��־д��
     * @access protected
     * @param  array  $message ��־��Ϣ
     * @param  string $destination ��־�ļ�
     * @param  bool   $apart �Ƿ�����ļ�д��
     * @param  bool   $append �Ƿ�׷��������Ϣ
     * @return bool
     */
    protected function write(array $message, string $destination, bool $apart = false, bool $append = false): bool
    {
        // �����־�ļ���С���������ô�С�򱸷���־�ļ���������
        $this->checkLogSize($destination);

        $info = [];
        // ��־��Ϣ��װ
        $info['timestamp'] = date($this->config['time_format']);

        foreach ($message as $type => $msg) {
            $info[$type] = is_array($msg) ? implode(PHP_EOL, $msg) : $msg;
        }

        if ($this->isCli) {
            $message = $this->parseCliLog($info);
        } else {
            // ��ӵ�����־
            $this->getDebugLog($info, $append, $apart);

            $message = $this->parseLog($info);
        }

        return error_log($message, 3, $destination);
    }

    /**
     * ��ȡ����־�ļ���
     * @access public
     * @return string
     */
    protected function getMasterLogFile(): string
    {
        if (empty($this->config['path'])) {
            $this->config['path'] = $this->app->getRuntimePath() . 'log' . DIRECTORY_SEPARATOR;
        } elseif (substr($this->config['path'], -1) != DIRECTORY_SEPARATOR) {
            $this->config['path'] .= DIRECTORY_SEPARATOR;
        }

        if ($this->config['max_files']) {
            $files = glob($this->config['path'] . '*.log');

            try {
                if (count($files) > $this->config['max_files']) {
                    unlink($files[0]);
                }
            } catch (\Exception $e) {
                //
            }
        }

        if ($this->config['single']) {
            $name = is_string($this->config['single']) ? $this->config['single'] : 'single';

            $destination = $this->config['path'] . $name . '.log';
        } else {
            $cli = $this->isCli ? '_cli' : '';

            if ($this->config['max_files']) {
                $filename = date('Ymd') . $cli . '.log';
            } else {
                $filename = date('Ym') . DIRECTORY_SEPARATOR . date('d') . $cli . '.log';
            }

            $destination = $this->config['path'] . $filename;
        }

        return $destination;
    }

    /**
     * ��ȡ������־�ļ���
     * @access public
     * @param  string $path ��־Ŀ¼
     * @param  string $type ��־����
     * @return string
     */
    protected function getApartLevelFile(string $path, string $type): string
    {
        $cli = $this->isCli ? '_cli' : '';

        if ($this->config['single']) {
            $name = is_string($this->config['single']) ? $this->config['single'] : 'single';

            $name .= '_' . $type;
        } elseif ($this->config['max_files']) {
            $name = date('Ymd') . '_' . $type . $cli;
        } else {
            $name = date('d') . '_' . $type . $cli;
        }

        return $path . DIRECTORY_SEPARATOR . $name . '.log';
    }

    /**
     * �����־�ļ���С���Զ����ɱ����ļ�
     * @access protected
     * @param  string $destination ��־�ļ�
     * @return void
     */
    protected function checkLogSize(string $destination): void
    {
        if (is_file($destination) && floor($this->config['file_size']) <= filesize($destination)) {
            try {
                rename($destination, dirname($destination) . DIRECTORY_SEPARATOR . time() . '-' . basename($destination));
            } catch (\Exception $e) {
                //
            }
        }
    }

    /**
     * CLI��־����
     * @access protected
     * @param  array $info ��־��Ϣ
     * @return string
     */
    protected function parseCliLog(array $info): string
    {
        if ($this->config['json']) {
            $message = json_encode($info, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        } else {
            $now = $info['timestamp'];
            unset($info['timestamp']);

            $message = implode(PHP_EOL, $info);

            $message = "[{$now}]" . $message . PHP_EOL;
        }

        return $message;
    }

    /**
     * ������־
     * @access protected
     * @param  array $info ��־��Ϣ
     * @return string
     */
    protected function parseLog(array $info): string
    {
        $requestInfo = [
            'ip'     => $this->app->request->ip(),
            'method' => $this->app->request->method(),
            'host'   => $this->app->request->host(),
            'uri'    => $this->app->request->url(),
        ];

        if ($this->config['json']) {
            $info = $requestInfo + $info;
            return json_encode($info, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        }

        array_unshift($info, "---------------------------------------------------------------" . PHP_EOL . "[{$info['timestamp']}] {$requestInfo['ip']} {$requestInfo['method']} {$requestInfo['host']}{$requestInfo['uri']}");
        unset($info['timestamp']);

        return implode(PHP_EOL, $info) . PHP_EOL;
    }

    protected function getDebugLog(&$info, $append, $apart): void
    {
        if ($this->app->isDebug() && $append) {

            if ($this->config['json']) {
                // ��ȡ������Ϣ
                $runtime = round(microtime(true) - $this->app->getBeginTime(), 10);
                $reqs    = $runtime > 0 ? number_format(1 / $runtime, 2) : '��';

                $memory_use = number_format((memory_get_usage() - $this->app->getBeginMem()) / 1024, 2);

                $info = [
                    'runtime' => number_format($runtime, 6) . 's',
                    'reqs'    => $reqs . 'req/s',
                    'memory'  => $memory_use . 'kb',
                    'file'    => count(get_included_files()),
                ] + $info;

            } elseif (!$apart) {
                // ���Ӷ���ĵ�����Ϣ
                $runtime = round(microtime(true) - $this->app->getBeginTime(), 10);
                $reqs    = $runtime > 0 ? number_format(1 / $runtime, 2) : '��';

                $memory_use = number_format((memory_get_usage() - $this->app->getBeginMem()) / 1024, 2);

                $time_str   = '[����ʱ�䣺' . number_format($runtime, 6) . 's] [�����ʣ�' . $reqs . 'req/s]';
                $memory_str = ' [�ڴ����ģ�' . $memory_use . 'kb]';
                $file_load  = ' [�ļ����أ�' . count(get_included_files()) . ']';

                array_unshift($info, $time_str . $memory_str . $file_load);
            }
        }
    }
}
