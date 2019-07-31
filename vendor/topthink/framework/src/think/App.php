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

use Opis\Closure\SerializableClosure;
use think\exception\ClassNotFoundException;
use think\initializer\BootService;
use think\initializer\Error;
use think\initializer\RegisterService;

/**
 * App ������
 */
class App extends Container
{
    const VERSION = '6.0.0RC3';

    /**
     * Ӧ�õ���ģʽ
     * @var bool
     */
    protected $appDebug = false;

    /**
     * Ӧ�ÿ�ʼʱ��
     * @var float
     */
    protected $beginTime;

    /**
     * Ӧ���ڴ��ʼռ��
     * @var integer
     */
    protected $beginMem;

    /**
     * ��ǰӦ����������ռ�
     * @var string
     */
    protected $namespace = 'app';

    /**
     * Ӧ�ø�Ŀ¼
     * @var string
     */
    protected $rootPath = '';

    /**
     * ���Ŀ¼
     * @var string
     */
    protected $thinkPath = '';

    /**
     * Ӧ��Ŀ¼
     * @var string
     */
    protected $appPath = '';

    /**
     * RuntimeĿ¼
     * @var string
     */
    protected $runtimePath = '';

    /**
     * ���ú�׺
     * @var string
     */
    protected $configExt = '.php';

    /**
     * Ӧ�ó�ʼ����
     * @var array
     */
    protected $initializers = [
        Error::class,
        RegisterService::class,
        BootService::class,
    ];

    /**
     * ע���ϵͳ����
     * @var array
     */
    protected $services = [];

    /**
     * ��ʼ��
     * @var bool
     */
    protected $initialized = false;

    /**
     * �ܹ�����
     * @access public
     * @param string $rootPath Ӧ�ø�Ŀ¼
     */
    public function __construct(string $rootPath = '')
    {
        $this->thinkPath   = dirname(__DIR__) . DIRECTORY_SEPARATOR;
        $this->rootPath    = $rootPath ? rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : $this->getDefaultRootPath();
        $this->appPath     = $this->rootPath . 'app' . DIRECTORY_SEPARATOR;
        $this->runtimePath = $this->rootPath . 'runtime' . DIRECTORY_SEPARATOR;

        if (is_file($this->appPath . 'provider.php')) {
            $this->bind(include $this->appPath . 'provider.php');
        }

        static::setInstance($this);

        $this->instance('app', $this);
        $this->instance('think\Container', $this);
    }

    /**
     * ע�����
     * @access public
     * @param Service|string $service ����
     * @param bool           $force   ǿ������ע��
     * @return Service|null
     */
    public function register($service, bool $force = false)
    {
        $registered = $this->getService($service);

        if ($registered && !$force) {
            return $registered;
        }

        if (is_string($service)) {
            $service = new $service($this);
        }

        if (method_exists($service, 'register')) {
            $service->register();
        }

        if (property_exists($service, 'bind')) {
            $this->bind($service->bind);
        }

        $this->services[] = $service;
    }

    /**
     * ִ�з���
     * @access public
     * @param Service $service ����
     * @return mixed
     */
    public function bootService($service)
    {
        if (method_exists($service, 'boot')) {
            return $this->invoke([$service, 'boot']);
        }
    }

    /**
     * ��ȡ����
     * @param string|Service $service
     * @return Service|null
     */
    public function getService($service)
    {
        $name = is_string($service) ? $service : get_class($service);
        return array_values(array_filter($this->services, function ($value) use ($name) {
            return $value instanceof $name;
        }, ARRAY_FILTER_USE_BOTH))[0] ?? null;
    }

    /**
     * ����Ӧ�õ���ģʽ
     * @access public
     * @param bool $debug ����Ӧ�õ���ģʽ
     * @return $this
     */
    public function debug(bool $debug = true)
    {
        $this->appDebug = $debug;
        return $this;
    }

    /**
     * �Ƿ�Ϊ����ģʽ
     * @access public
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->appDebug;
    }

    /**
     * ����Ӧ�������ռ�
     * @access public
     * @param string $namespace Ӧ�������ռ�
     * @return $this
     */
    public function setNamespace(string $namespace)
    {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * ��ȡӦ����������ռ�
     * @access public
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * ��ȡ��ܰ汾
     * @access public
     * @return string
     */
    public function version(): string
    {
        return static::VERSION;
    }

    /**
     * ��ȡӦ�ø�Ŀ¼
     * @access public
     * @return string
     */
    public function getRootPath(): string
    {
        return $this->rootPath;
    }

    /**
     * ��ȡӦ�û���Ŀ¼
     * @access public
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->rootPath . 'app' . DIRECTORY_SEPARATOR;
    }

    /**
     * ��ȡ��ǰӦ��Ŀ¼
     * @access public
     * @return string
     */
    public function getAppPath(): string
    {
        return $this->appPath;
    }

    /**
     * ����Ӧ��Ŀ¼
     * @param $path
     */
    public function setAppPath($path)
    {
        $this->appPath = $path;
    }

    /**
     * ��ȡӦ������ʱĿ¼
     * @access public
     * @return string
     */
    public function getRuntimePath(): string
    {
        return $this->runtimePath;
    }

    /**
     * ����runtimeĿ¼
     * @param $path
     */
    public function setRuntimePath($path)
    {
        $this->runtimePath = $path;
    }

    /**
     * ��ȡ���Ŀ��Ŀ¼
     * @access public
     * @return string
     */
    public function getThinkPath(): string
    {
        return $this->thinkPath;
    }

    /**
     * ��ȡӦ������Ŀ¼
     * @access public
     * @return string
     */
    public function getConfigPath(): string
    {
        return $this->rootPath . 'config' . DIRECTORY_SEPARATOR;
    }

    /**
     * ��ȡ���ú�׺
     * @access public
     * @return string
     */
    public function getConfigExt(): string
    {
        return $this->configExt;
    }

    /**
     * ��ȡӦ�ÿ���ʱ��
     * @access public
     * @return float
     */
    public function getBeginTime(): float
    {
        return $this->beginTime;
    }

    /**
     * ��ȡӦ�ó�ʼ�ڴ�ռ��
     * @access public
     * @return integer
     */
    public function getBeginMem(): int
    {
        return $this->beginMem;
    }

    /**
     * ��ʼ��Ӧ��
     * @access public
     * @return $this
     */
    public function initialize()
    {
        $this->initialized = true;

        $this->beginTime = microtime(true);
        $this->beginMem  = memory_get_usage();

        // ���ػ�������
        if (is_file($this->rootPath . '.env')) {
            $this->env->load($this->rootPath . '.env');
        }

        $this->configExt = $this->env->get('config_ext', '.php');

        // ����ȫ�ֳ�ʼ���ļ�
        if (is_file($this->getRuntimePath() . 'init.php')) {
            //ֱ�Ӽ��ػ���
            include $this->getRuntimePath() . 'init.php';
        } else {
            $this->load();
        }

        $this->debugModeInit();

        // ���ؿ��Ĭ�����԰�
        $langSet = $this->lang->defaultLangSet();

        $this->lang->load($this->thinkPath . 'lang' . DIRECTORY_SEPARATOR . $langSet . '.php');

        // ����Ӧ��Ĭ�����԰�
        $this->loadLangPack($langSet);

        // ����AppInit
        $this->event->trigger('AppInit');

        date_default_timezone_set($this->config->get('app.default_timezone', 'Asia/Shanghai'));

        // ��ʼ��
        foreach ($this->initializers as $initializer) {
            $this->make($initializer)->init($this);
        }

        return $this;
    }

    /**
     * �Ƿ��ʼ����
     * @return bool
     */
    public function initialized()
    {
        return $this->initialized;
    }

    /**
     * �������԰�
     * @param string $langset ����
     * @return void
     */
    public function loadLangPack($langset)
    {
        if (empty($langset)) {
            return;
        }

        // ����ϵͳ���԰�
        $files = glob($this->appPath . 'lang' . DIRECTORY_SEPARATOR . $langset . '.*');
        $this->lang->load($files);

        // ������չ���Զ��壩���԰�
        $list = $this->config->get('lang.extend_list', []);

        if (isset($list[$langset])) {
            $this->lang->load($list[$langset]);
        }
    }

    /**
     * ����Ӧ��
     * @access public
     * @return void
     */
    public function boot(): void
    {
        array_walk($this->services, function ($service) {
            $this->bootService($service);
        });
    }

    /**
     * ����Ӧ���ļ�������
     * @access protected
     * @return void
     */
    protected function load(): void
    {
        $appPath = $this->getAppPath();

        if (is_file($appPath . 'common.php')) {
            include_once $appPath . 'common.php';
        }

        include $this->thinkPath . 'helper.php';

        $configPath = $this->getConfigPath();

        $files = [];

        if (is_dir($configPath)) {
            $files = glob($configPath . '*' . $this->configExt);
        }

        foreach ($files as $file) {
            $this->config->load($file, pathinfo($file, PATHINFO_FILENAME));
        }

        if (is_file($appPath . 'event.php')) {
            $this->loadEvent(include $appPath . 'event.php');
        }

        if (is_file($appPath . 'service.php')) {
            $services = include $appPath . 'service.php';
            foreach ($services as $service) {
                $this->register($service);
            }
        }
    }

    /**
     * ����ģʽ����
     * @access protected
     * @return void
     */
    protected function debugModeInit(): void
    {
        // Ӧ�õ���ģʽ
        if (!$this->appDebug) {
            $this->appDebug = $this->env->get('app_debug') ? true : false;
            ini_set('display_errors', 'Off');
        }

        if (!$this->runningInConsole()) {
            //��������һ��Ƚϴ��buffer
            if (ob_get_level() > 0) {
                $output = ob_get_clean();
            }
            ob_start();
            if (!empty($output)) {
                echo $output;
            }
        }
    }

    /**
     * ע��Ӧ���¼�
     * @access protected
     * @param array $event �¼�����
     * @return void
     */
    public function loadEvent(array $event): void
    {
        if (isset($event['bind'])) {
            $this->event->bind($event['bind']);
        }

        if (isset($event['listen'])) {
            $this->event->listenEvents($event['listen']);
        }

        if (isset($event['subscribe'])) {
            $this->event->subscribe($event['subscribe']);
        }
    }

    /**
     * ����Ӧ���������
     * @access public
     * @param string $layer ���� controller model ...
     * @param string $name  ����
     * @return string
     */
    public function parseClass(string $layer, string $name): string
    {
        $name  = str_replace(['/', '.'], '\\', $name);
        $array = explode('\\', $name);
        $class = self::parseName(array_pop($array), 1);
        $path  = $array ? implode('\\', $array) . '\\' : '';

        return $this->namespace . '\\' . $layer . '\\' . $path . $class;
    }

    /**
     * �Ƿ���������������
     * @return bool
     */
    public function runningInConsole()
    {
        return php_sapi_name() === 'cli' || php_sapi_name() === 'phpdbg';
    }

    /**
     * ��ȡӦ�ø�Ŀ¼
     * @access protected
     * @return string
     */
    protected function getDefaultRootPath(): string
    {
        $path = dirname(dirname(dirname(dirname($this->thinkPath))));

        return $path . DIRECTORY_SEPARATOR;
    }

    /**
     * �ַ����������ת��
     * type 0 ��Java���ת��ΪC�ķ�� 1 ��C���ת��ΪJava�ķ��
     * @access public
     * @param string  $name    �ַ���
     * @param integer $type    ת������
     * @param bool    $ucfirst ����ĸ�Ƿ��д���շ����
     * @return string
     */
    public static function parseName(string $name = null, int $type = 0, bool $ucfirst = true): string
    {
        if ($type) {
            $name = preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                return strtoupper($match[1]);
            }, $name);
            return $ucfirst ? ucfirst($name) : lcfirst($name);
        }

        return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
    }

    /**
     * ��ȡ����(�����������ռ�)
     * @access public
     * @param string|object $class
     * @return string
     */
    public static function classBaseName($class): string
    {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }

    /**
     * ������������ʵ��
     * @access public
     * @param string $name      ��������
     * @param string $namespace Ĭ�������ռ�
     * @param array  $args
     * @return mixed
     */
    public static function factory(string $name, string $namespace = '', ...$args)
    {
        $class = false !== strpos($name, '\\') ? $name : $namespace . ucwords($name);

        if (class_exists($class)) {
            return Container::getInstance()->invokeClass($class, $args);
        }

        throw new ClassNotFoundException('class not exists:' . $class, $class);
    }

    /**
     * @param $data
     * @codeCoverageIgnore
     * @return string
     */
    public static function serialize($data): string
    {
        SerializableClosure::enterContext();
        SerializableClosure::wrapClosures($data);
        $data = \serialize($data);
        SerializableClosure::exitContext();
        return $data;
    }

    /**
     * @param string $data
     * @codeCoverageIgnore
     * @return mixed|string
     */
    public static function unserialize(string $data)
    {
        SerializableClosure::enterContext();
        $data = \unserialize($data);
        SerializableClosure::unwrapClosures($data);
        SerializableClosure::exitContext();
        return $data;
    }
}
