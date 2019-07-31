<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2015 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

namespace think\console\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;

class Build extends Command
{

    /**
     * Ӧ�û���Ŀ¼
     * @var string
     */
    protected $basePath;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('build')
            ->addArgument('app', Argument::OPTIONAL, 'app name .')
            ->setDescription('Build Application Dirs');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->basePath = $this->app->getBasePath();
        $app            = $input->getArgument('app') ?: '';

        if (empty($app) && !is_dir($this->basePath . 'controller')) {
            $output->writeln('<error>Miss app name!</error>');
            return false;
        }

        $list = include $this->basePath . 'build.php';

        if (empty($list)) {
            $output->writeln("Build file Is Empty");
            return;
        }

        $this->buildApp($app, $list);
        $output->writeln("Successed");

    }

    /**
     * ����Ӧ��
     * @access protected
     * @param  string $name Ӧ����
     * @param  array  $list Ӧ��Ŀ¼�ṹ
     * @return void
     */
    protected function buildApp(string $app, array $list = []): void
    {
        if (!is_dir($this->basePath . $app)) {
            // ����Ӧ��Ŀ¼
            mkdir($this->basePath . $app);
        }

        $appPath   = $this->basePath . ($app ? $app . DIRECTORY_SEPARATOR : '');
        $namespace = 'app' . ($app ? '\\' . $app : '');

        // ���������ļ��͹����ļ�
        $this->buildCommon($app);
        // ����ģ���Ĭ��ҳ��
        $this->buildHello($app, $namespace);

        foreach ($list as $path => $file) {
            if ('__dir__' == $path) {
                // ������Ŀ¼
                foreach ($file as $dir) {
                    $this->checkDirBuild($appPath . $dir);
                }
            } elseif ('__file__' == $path) {
                // ���ɣ��հף��ļ�
                foreach ($file as $name) {
                    if (!is_file($appPath . $name)) {
                        file_put_contents($appPath . $name, 'php' == pathinfo($name, PATHINFO_EXTENSION) ? '<?php' . PHP_EOL : '');
                    }
                }
            } else {
                // �������MVC�ļ�
                foreach ($file as $val) {
                    $val      = trim($val);
                    $filename = $appPath . $path . DIRECTORY_SEPARATOR . $val . '.php';
                    $space    = $namespace . '\\' . $path;
                    $class    = $val;
                    switch ($path) {
                        case 'controller': // ������
                            if ($this->app->config->get('route.controller_suffix')) {
                                $filename = $appPath . $path . DIRECTORY_SEPARATOR . $val . 'Controller.php';
                                $class    = $val . 'Controller';
                            }
                            $content = "<?php" . PHP_EOL . "namespace {$space};" . PHP_EOL . PHP_EOL . "class {$class}" . PHP_EOL . "{" . PHP_EOL . PHP_EOL . "}";
                            break;
                        case 'model': // ģ��
                            $content = "<?php" . PHP_EOL . "namespace {$space};" . PHP_EOL . PHP_EOL . "use think\Model;" . PHP_EOL . PHP_EOL . "class {$class} extends Model" . PHP_EOL . "{" . PHP_EOL . PHP_EOL . "}";
                            break;
                        case 'view': // ��ͼ
                            $filename = $appPath . $path . DIRECTORY_SEPARATOR . $val . '.html';
                            $this->checkDirBuild(dirname($filename));
                            $content = '';
                            break;
                        default:
                            // �����ļ�
                            $content = "<?php" . PHP_EOL . "namespace {$space};" . PHP_EOL . PHP_EOL . "class {$class}" . PHP_EOL . "{" . PHP_EOL . PHP_EOL . "}";
                    }

                    if (!is_file($filename)) {
                        file_put_contents($filename, $content);
                    }
                }
            }
        }
    }

    /**
     * ����Ӧ�õĻ�ӭҳ��
     * @access protected
     * @param  string $appName Ӧ����
     * @param  string $namespace Ӧ����������ռ�
     * @return void
     */
    protected function buildHello(string $appName, string $namespace): void
    {
        $suffix   = $this->app->config->get('route.controller_suffix') ? 'Controller' : '';
        $filename = $this->basePath . ($appName ? $appName . DIRECTORY_SEPARATOR : '') . 'controller' . DIRECTORY_SEPARATOR . 'Index' . $suffix . '.php';

        if (!is_file($filename)) {
            $content = file_get_contents($this->app->getThinkPath() . 'tpl' . DIRECTORY_SEPARATOR . 'default_index.tpl');
            $content = str_replace(['{%name%}', '{%app%}', '{%layer%}', '{%suffix%}'], [$appName, $namespace, 'controller', $suffix], $content);
            $this->checkDirBuild(dirname($filename));

            file_put_contents($filename, $content);
        }
    }

    /**
     * ����Ӧ�õĹ����ļ�
     * @access protected
     * @param  string $appName Ӧ������
     * @return void
     */
    protected function buildCommon(string $appName): void
    {
        $appPath = $this->basePath . ($appName ? $appName . DIRECTORY_SEPARATOR : '');

        if (!is_file($appPath . 'common.php')) {
            file_put_contents($appPath . 'common.php', "<?php" . PHP_EOL . "// ����ϵͳ�Զ����ɵ�{$appName}Ӧ�ù����ļ�" . PHP_EOL);
        }

        foreach (['event', 'middleware', 'provider'] as $name) {
            if (!is_file($appPath . $name . '.php')) {
                file_put_contents($appPath . $name . '.php', "<?php" . PHP_EOL . "// ����ϵͳ�Զ����ɵ�{$appName}Ӧ��{$name}�����ļ�" . PHP_EOL . "return [" . PHP_EOL . PHP_EOL . "];" . PHP_EOL);
            }
        }
    }

    /**
     * ����Ŀ¼
     * @access protected
     * @param  string $dirname Ŀ¼����
     * @return void
     */
    protected function checkDirBuild(string $dirname): void
    {
        if (!is_dir($dirname)) {
            mkdir($dirname, 0755, true);
        }
    }
}
