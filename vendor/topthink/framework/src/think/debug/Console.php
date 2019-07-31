<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yangweijie <yangweijiester@gmail.com>
// +----------------------------------------------------------------------
declare (strict_types = 1);
namespace think\debug;

use think\App;
use think\Response;

/**
 * ������������
 */
class Console
{
    protected $config = [
        'tabs' => ['base' => '����', 'file' => '�ļ�', 'info' => '����', 'notice|error' => '����', 'sql' => 'SQL', 'debug|log' => '����'],
    ];

    // ʵ�������������
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * ��������ӿ�
     * @access public
     * @param  Response  $response Response����
     * @param  array     $log ��־��Ϣ
     * @return string|bool
     */
    public function output(App $app, Response $response, array $log = [])
    {
        $request     = $app->request;
        $contentType = $response->getHeader('Content-Type');
        $accept      = $request->header('accept');
        if (strpos($accept, 'application/json') === 0 || $request->isAjax()) {
            return false;
        } elseif (!empty($contentType) && strpos($contentType, 'html') === false) {
            return false;
        }
        // ��ȡ������Ϣ
        $runtime = number_format(microtime(true) - $app->getBeginTime(), 10);
        $reqs    = $runtime > 0 ? number_format(1 / $runtime, 2) : '��';
        $mem     = number_format((memory_get_usage() - $app->getBeginMem()) / 1024, 2);

        if ($request->host()) {
            $uri = $request->protocol() . ' ' . $request->method() . ' : ' . $request->url(true);
        } else {
            $uri = 'cmd:' . implode(' ', $_SERVER['argv']);
        }

        // ҳ��Trace��Ϣ
        $base = [
            '������Ϣ' => date('Y-m-d H:i:s', $request->time()) . ' ' . $uri,
            '����ʱ��' => number_format((float) $runtime, 6) . 's [ �����ʣ�' . $reqs . 'req/s ] �ڴ����ģ�' . $mem . 'kb �ļ����أ�' . count(get_included_files()),
            '��ѯ��Ϣ' => $app->db->getQueryTimes() . ' queries',
            '������Ϣ' => $app->cache->getReadTimes() . ' reads,' . $app->cache->getWriteTimes() . ' writes',
        ];

        if ($app->session->getId(false)) {
            $base['�Ự��Ϣ'] = 'SESSION_ID=' . $app->session->getId();
        }

        $info = $this->getFileInfo();

        // ҳ��Trace��Ϣ
        $trace = [];
        foreach ($this->config['tabs'] as $name => $title) {
            $name = strtolower($name);
            switch ($name) {
                case 'base': // ������Ϣ
                    $trace[$title] = $base;
                    break;
                case 'file': // �ļ���Ϣ
                    $trace[$title] = $info;
                    break;
                default: // ������Ϣ
                    if (strpos($name, '|')) {
                        // ������Ϣ
                        $names  = explode('|', $name);
                        $result = [];
                        foreach ($names as $item) {
                            $result = array_merge($result, $log[$item] ?? []);
                        }
                        $trace[$title] = $result;
                    } else {
                        $trace[$title] = $log[$name] ?? '';
                    }
            }
        }

        //���������̨
        $lines = '';
        foreach ($trace as $type => $msg) {
            $lines .= $this->console($type, $msg);
        }
        $js = <<<JS

<script type='text/javascript'>
{$lines}
</script>
JS;
        return $js;
    }

    protected function console(string $type, $msg)
    {
        $type       = strtolower($type);
        $trace_tabs = array_values($this->config['tabs']);
        $line       = [];
        $line[]     = ($type == $trace_tabs[0] || '����' == $type || '����' == $type)
        ? "console.group('{$type}');"
        : "console.groupCollapsed('{$type}');";

        foreach ((array) $msg as $key => $m) {
            switch ($type) {
                case '����':
                    $var_type = gettype($m);
                    if (in_array($var_type, ['array', 'string'])) {
                        $line[] = "console.log(" . json_encode($m) . ");";
                    } else {
                        $line[] = "console.log(" . json_encode(var_export($m, true)) . ");";
                    }
                    break;
                case '����':
                    $msg    = str_replace("\n", '\n', addslashes(is_scalar($m) ? $m : json_encode($m)));
                    $style  = 'color:#F4006B;font-size:14px;';
                    $line[] = "console.error(\"%c{$msg}\", \"{$style}\");";
                    break;
                case 'sql':
                    $msg    = str_replace("\n", '\n', addslashes($m));
                    $style  = "color:#009bb4;";
                    $line[] = "console.log(\"%c{$msg}\", \"{$style}\");";
                    break;
                default:
                    $m      = is_string($key) ? $key . ' ' . $m : $key + 1 . ' ' . $m;
                    $msg    = json_encode($m);
                    $line[] = "console.log({$msg});";
                    break;
            }
        }
        $line[] = "console.groupEnd();";
        return implode(PHP_EOL, $line);
    }

    /**
     * ��ȡ�ļ�������Ϣ
     * @access protected
     * @return integer|array
     */
    protected function getFileInfo()
    {
        $files = get_included_files();
        $info  = [];

        foreach ($files as $key => $file) {
            $info[] = $file . ' ( ' . number_format(filesize($file) / 1024, 2) . ' KB )';
        }

        return $info;
    }
}
