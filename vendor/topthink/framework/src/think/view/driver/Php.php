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

namespace think\view\driver;

use think\App;
use think\contract\TemplateHandlerInterface;
use think\template\exception\TemplateNotFoundException;

/**
 * PHPԭ��ģ������
 */
class Php implements TemplateHandlerInterface
{
    protected $template;
    protected $content;
    protected $app;

    // ģ���������
    protected $config = [
        // Ĭ��ģ����Ⱦ���� 1 ����ΪСд+�»��� 2 ȫ��ת��Сд 3 ���ֲ�������
        'auto_rule'   => 1,
        // ��ͼ����Ŀ¼������ʽ��
        'view_base'   => '',
        // ģ����ʼ·��
        'view_path'   => '',
        // ģ���ļ���׺
        'view_suffix' => 'php',
        // ģ���ļ����ָ���
        'view_depr'   => DIRECTORY_SEPARATOR,
    ];

    public function __construct(App $app, array $config = [])
    {
        $this->app    = $app;
        $this->config = array_merge($this->config, (array) $config);
    }

    /**
     * ����Ƿ����ģ���ļ�
     * @access public
     * @param  string $template ģ���ļ�����ģ�����
     * @return bool
     */
    public function exists(string $template): bool
    {
        if ('' == pathinfo($template, PATHINFO_EXTENSION)) {
            // ��ȡģ���ļ���
            $template = $this->parseTemplate($template);
        }

        return is_file($template);
    }

    /**
     * ��Ⱦģ���ļ�
     * @access public
     * @param  string $template ģ���ļ�
     * @param  array  $data ģ�����
     * @return void
     */
    public function fetch(string $template, array $data = []): void
    {
        if ('' == pathinfo($template, PATHINFO_EXTENSION)) {
            // ��ȡģ���ļ���
            $template = $this->parseTemplate($template);
        }

        // ģ�岻���� �׳��쳣
        if (!is_file($template)) {
            throw new TemplateNotFoundException('template not exists:' . $template, $template);
        }

        $this->template = $template;

        // ��¼��ͼ��Ϣ
        $this->app->log
            ->record('[ VIEW ] ' . $template . ' [ ' . var_export(array_keys($data), true) . ' ]');

        extract($data, EXTR_OVERWRITE);

        include $this->template;
    }

    /**
     * ��Ⱦģ������
     * @access public
     * @param  string $content ģ������
     * @param  array  $data ģ�����
     * @return void
     */
    public function display(string $content, array $data = []): void
    {
        $this->content = $content;

        extract($data, EXTR_OVERWRITE);
        eval('?>' . $this->content);
    }

    /**
     * �Զ���λģ���ļ�
     * @access private
     * @param  string $template ģ���ļ�����
     * @return string
     */
    private function parseTemplate(string $template): string
    {
        if (empty($this->config['view_path'])) {
            $this->config['view_path'] = $this->app->getAppPath() . 'view' . DIRECTORY_SEPARATOR;
        }

        $request = $this->app->request;

        // ��ȡ��ͼ��Ŀ¼
        if (strpos($template, '@')) {
            // ��ģ�����
            list($app, $template) = explode('@', $template);
        }

        if ($this->config['view_base']) {
            // ������ͼĿ¼
            $app  = isset($app) ? $app : $request->app();
            $path = $this->config['view_base'] . ($app ? $app . DIRECTORY_SEPARATOR : '');
        } else {
            $path = isset($app) ? $this->app->getBasePath() . $app . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR : $this->config['view_path'];
        }

        $depr = $this->config['view_depr'];

        if (0 !== strpos($template, '/')) {
            $template   = str_replace(['/', ':'], $depr, $template);
            $controller = App::parseName($request->controller());

            if ($controller) {
                if ('' == $template) {
                    // ���ģ���ļ���Ϊ�� ����Ĭ�Ϲ���λ
                    if (2 == $this->config['auto_rule']) {
                        $template = $request->action(true);
                    } elseif (3 == $this->config['auto_rule']) {
                        $template = $request->action();
                    } else {
                        $template = App::parseName($request->action());
                    }

                    $template = str_replace('.', DIRECTORY_SEPARATOR, $controller) . $depr . $template;
                } elseif (false === strpos($template, $depr)) {
                    $template = str_replace('.', DIRECTORY_SEPARATOR, $controller) . $depr . $template;
                }
            }
        } else {
            $template = str_replace(['/', ':'], $depr, substr($template, 1));
        }

        return $path . ltrim($template, '/') . '.' . ltrim($this->config['view_suffix'], '.');
    }

    /**
     * ����ģ������
     * @access private
     * @param  array $config ����
     * @return void
     */
    public function config(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * ��ȡģ����������
     * @access public
     * @param  string $name ������
     * @return mixed
     */
    public function getConfig(string $name)
    {
        return $this->config[$name] ?? null;
    }
}
