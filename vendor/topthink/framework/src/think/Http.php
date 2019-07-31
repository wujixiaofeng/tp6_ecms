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

use Closure;
use think\exception\Handle;
use think\exception\HttpException;
use Throwable;

/**
 * WebӦ�ù�����
 */
class Http
{

    /**
     * @var App
     */
    protected $app;

    /**
     * Ӧ��·��
     * @var string
     */
    protected $path;

    /**
     * �Ƿ��Ӧ��ģʽ
     * @var bool
     */
    protected $multi = false;

    /**
     * �Ƿ�������Ӧ��
     * @var bool
     */
    protected $bindDomain = false;

    /**
     * Ӧ������
     * @var string
     */
    protected $name;

    public function __construct(App $app)
    {
        $this->app   = $app;
        $this->multi = is_dir($this->app->getBasePath() . 'controller') ? false : true;
    }

    /**
     * �Ƿ�������Ӧ��
     * @access public
     * @return bool
     */
    public function isBindDomain(): bool
    {
        return $this->bindDomain;
    }

    /**
     * ����Ӧ��ģʽ
     * @access public
     * @param bool $multi
     * @return $this
     */
    public function multi(bool $multi)
    {
        $this->multi = $multi;
        return $this;
    }

    /**
     * �Ƿ�Ϊ��Ӧ��ģʽ
     * @access public
     * @return bool
     */
    public function isMulti(): bool
    {
        return $this->multi;
    }

    /**
     * ����Ӧ������
     * @access public
     * @param string $name Ӧ������
     * @return $this
     */
    public function name(string $name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * ��ȡӦ������
     * @access public
     * @return string
     */
    public function getName(): string
    {
        return $this->name ?: '';
    }

    /**
     * ����Ӧ��Ŀ¼
     * @access public
     * @param string $path Ӧ��Ŀ¼
     * @return $this
     */
    public function path(string $path)
    {
        if (substr($path, -1) != DIRECTORY_SEPARATOR) {
            $path .= DIRECTORY_SEPARATOR;
        }

        $this->path = $path;
        return $this;
    }

    /**
     * ִ��Ӧ�ó���
     * @access public
     * @param Request|null $request
     * @return Response
     */
    public function run(Request $request = null): Response
    {
        //�Զ�����request����
        $request = $request ?? $this->app->make('request', [], true);
        $this->app->instance('request', $request);

        try {
            $response = $this->runWithRequest($request);
        } catch (Throwable $e) {
            $this->reportException($e);

            $response = $this->renderException($request, $e);
        }

        return $response->setCookie($this->app->cookie);
    }

    /**
     * ��ʼ��
     */
    protected function initialize()
    {
        if (!$this->app->initialized()) {
            $this->app->initialize();
        }
    }

    /**
     * ִ��Ӧ�ó���
     * @param Request $request
     * @return mixed
     */
    protected function runWithRequest(Request $request)
    {
        $this->initialize();

        // ����ȫ���м��
        if (is_file($this->app->getBasePath() . 'middleware.php')) {
            $this->app->middleware->import(include $this->app->getBasePath() . 'middleware.php');
        }

        if ($this->multi) {
            $this->parseMultiApp();
        }

        // ���ÿ����¼�����
        $this->app->event->withEvent($this->app->config->get('app.with_event', true));

        // ����HttpRun
        $this->app->event->trigger('HttpRun');

        $withRoute = $this->app->config->get('app.with_route', true) ? function () {
            $this->loadRoutes();
        } : null;

        return $this->app->route->dispatch($request, $withRoute);
    }

    /**
     * ����·��
     * @access protected
     * @return void
     */
    protected function loadRoutes(): void
    {
        // ����·�ɶ���
        if (is_dir($this->getRoutePath())) {
            $files = glob($this->getRoutePath() . '*.php');
            foreach ($files as $file) {
                include $file;
            }
        }

        if ($this->app->route->config('route_annotation')) {
            // �Զ�����ע��·�ɶ���
            if ($this->app->isDebug()) {
                $this->app->console->call('route:build', [$this->name]);
            }

            $filename = $this->app->getRuntimePath() . 'build_route.php';

            if (is_file($filename)) {
                include $filename;
            }
        }
    }

    /**
     * ��ȡ·��Ŀ¼
     * @access protected
     * @return string
     */
    protected function getRoutePath(): string
    {
        return $this->app->getRootPath() . 'route' . DIRECTORY_SEPARATOR . ($this->isMulti() ? $this->getName() . DIRECTORY_SEPARATOR : '');
    }

    /**
     * Report the exception to the exception handler.
     *
     * @param Throwable $e
     * @return void
     */
    protected function reportException(Throwable $e)
    {
        $this->app->make(Handle::class)->report($e);
    }

    /**
     * Render the exception to a response.
     *
     * @param Request   $request
     * @param Throwable $e
     * @return Response
     */
    protected function renderException($request, Throwable $e)
    {
        return $this->app->make(Handle::class)->render($request, $e);
    }

    /**
     * ��ȡ��ǰ�����������
     * @access protected
     * @codeCoverageIgnore
     * @return string
     */
    protected function getScriptName(): string
    {
        if (isset($_SERVER['SCRIPT_FILENAME'])) {
            $file = $_SERVER['SCRIPT_FILENAME'];
        } elseif (isset($_SERVER['argv'][0])) {
            $file = realpath($_SERVER['argv'][0]);
        }

        return isset($file) ? pathinfo($file, PATHINFO_FILENAME) : '';
    }

    /**
     * ������Ӧ��
     */
    protected function parseMultiApp(): void
    {
        if ($this->app->config->get('app.auto_multi_app', false)) {
            // �Զ���Ӧ��ʶ��
            $this->bindDomain = false;

            $bind = $this->app->config->get('app.domain_bind', []);

            if (!empty($bind)) {
                // ��ȡ��ǰ������
                $subDomain = $this->app->request->subDomain();
                $domain    = $this->app->request->host(true);

                if (isset($bind[$domain])) {
                    $appName          = $bind[$domain];
                    $this->bindDomain = true;
                } elseif (isset($bind[$subDomain])) {
                    $appName          = $bind[$subDomain];
                    $this->bindDomain = true;
                } elseif (isset($bind['*'])) {
                    $appName          = $bind['*'];
                    $this->bindDomain = true;
                }
            }

            if (!$this->bindDomain) {
                $map  = $this->app->config->get('app.app_map', []);
                $deny = $this->app->config->get('app.deny_app_list', []);
                $path = $this->app->request->pathinfo();
                $name = current(explode('/', $path));

                if (isset($map[$name])) {
                    if ($map[$name] instanceof Closure) {
                        $result  = call_user_func_array($map[$name], [$this]);
                        $appName = $result ?: $name;
                    } else {
                        $appName = $map[$name];
                    }
                } elseif ($name && (false !== array_search($name, $map) || in_array($name, $deny))) {
                    throw new HttpException(404, 'app not exists:' . $name);
                } elseif ($name && isset($map['*'])) {
                    $appName = $map['*'];
                } else {
                    $appName = $name;
                }

                if ($name) {
                    $this->app->request->setRoot($name);
                    $this->app->request->setPathinfo(strpos($path, '/') ? ltrim(strstr($path, '/'), '/') : '');
                }
            }
        } else {
            $appName = $this->name ?: $this->getScriptName();
        }

        $this->loadApp($appName ?: $this->app->config->get('app.default_app', 'index'));
    }

    /**
     * ����Ӧ���ļ�
     * @param string $appName Ӧ����
     * @return void
     */
    protected function loadApp(string $appName): void
    {
        $this->name = $appName;
        $this->app->request->setApp($appName);
        $this->app->setAppPath($this->path ?: $this->app->getBasePath() . $appName . DIRECTORY_SEPARATOR);
        $this->app->setRuntimePath($this->app->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . $appName . DIRECTORY_SEPARATOR);

        //����app�ļ�
        if (is_dir($this->app->getAppPath())) {
            if (is_file($this->app->getRuntimePath() . 'init.php')) {
                //ֱ�Ӽ��ػ���
                include $this->app->getRuntimePath() . 'init.php';
            } else {
                $appPath = $this->app->getAppPath();

                if (is_file($appPath . 'common.php')) {
                    include_once $appPath . 'common.php';
                }

                $configPath = $this->app->getConfigPath();

                $files = [];

                if (is_dir($configPath . $appName)) {
                    $files = array_merge($files, glob($configPath . $appName . DIRECTORY_SEPARATOR . '*' . $this->app->getConfigExt()));
                } elseif (is_dir($appPath . 'config')) {
                    $files = array_merge($files, glob($appPath . 'config' . DIRECTORY_SEPARATOR . '*' . $this->app->getConfigExt()));
                }

                foreach ($files as $file) {
                    $this->app->config->load($file, pathinfo($file, PATHINFO_FILENAME));
                }

                if (is_file($appPath . 'event.php')) {
                    $this->app->loadEvent(include $appPath . 'event.php');
                }

                if (is_file($appPath . 'middleware.php')) {
                    $this->app->middleware->import(include $appPath . 'middleware.php');
                }

                if (is_file($appPath . 'provider.php')) {
                    $this->app->bind(include $appPath . 'provider.php');
                }
            }
        }

        // ����Ӧ��Ĭ�����԰�
        $this->app->loadLangPack($this->app->lang->defaultLangSet());

        // ����Ӧ�������ռ�
        $this->app->setNamespace($this->app->config->get('app.app_namespace') ?: 'app\\' . $appName);
    }

    /**
     * HttpEnd
     * @param Response $response
     * @return void
     */
    public function end(Response $response): void
    {
        $this->app->event->trigger('HttpEnd', $response);

        // д����־
        $this->app->log->save();
        // д��Session
        $this->app->session->save();
    }

    public function __debugInfo()
    {
        return [
            'path'       => $this->path,
            'multi'      => $this->multi,
            'bindDomain' => $this->bindDomain,
            'name'       => $this->name,
        ];
    }
}
