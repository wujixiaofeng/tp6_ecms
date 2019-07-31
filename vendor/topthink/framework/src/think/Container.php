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

use ArrayAccess;
use ArrayIterator;
use Closure;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use think\exception\ClassNotFoundException;

/**
 * Class Container
 * @package think
 *
 * @property Route      $route
 * @property Config     $config
 * @property Cache      $cache
 * @property Request    $request
 * @property Http       $http
 * @property Console    $console
 * @property Env        $env
 * @property Event      $event
 * @property Middleware $middleware
 * @property Log        $log
 * @property Lang       $lang
 * @property Db         $db
 * @property Cookie     $cookie
 * @property Session    $session
 * @property Validate   $validate
 */
class Container implements ContainerInterface, ArrayAccess, IteratorAggregate, Countable
{
    /**
     * ��������ʵ��
     * @var Container|Closure
     */
    protected static $instance;

    /**
     * �����еĶ���ʵ��
     * @var array
     */
    protected $instances = [];

    /**
     * �����󶨱�ʶ
     * @var array
     */
    protected $bind = [
        'app'                     => App::class,
        'cache'                   => Cache::class,
        'config'                  => Config::class,
        'console'                 => Console::class,
        'cookie'                  => Cookie::class,
        'db'                      => Db::class,
        'env'                     => Env::class,
        'event'                   => Event::class,
        'http'                    => Http::class,
        'lang'                    => Lang::class,
        'log'                     => Log::class,
        'middleware'              => Middleware::class,
        'request'                 => Request::class,
        'response'                => Response::class,
        'route'                   => Route::class,
        'session'                 => Session::class,
        'validate'                => Validate::class,
        'view'                    => View::class,

        // �ӿ�����ע��
        'Psr\Log\LoggerInterface' => Log::class,
    ];

    /**
     * ��ȡ��ǰ������ʵ����������
     * @access public
     * @return static
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        if (static::$instance instanceof Closure) {
            return (static::$instance)();
        }

        return static::$instance;
    }

    /**
     * ���õ�ǰ������ʵ��
     * @access public
     * @param object|Closure $instance
     * @return void
     */
    public static function setInstance($instance): void
    {
        static::$instance = $instance;
    }

    /**
     * ��ȡ�����еĶ���ʵ�� �������򴴽�
     * @access public
     * @param string     $abstract    �������߱�ʶ
     * @param array|true $vars        ����
     * @param bool       $newInstance �Ƿ�ÿ�δ����µ�ʵ��
     * @return object
     */
    public static function pull(string $abstract, array $vars = [], bool $newInstance = false)
    {
        return static::getInstance()->make($abstract, $vars, $newInstance);
    }

    /**
     * ��ȡ�����еĶ���ʵ��
     * @access public
     * @param string $abstract �������߱�ʶ
     * @return object
     */
    public function get($abstract)
    {
        if ($this->has($abstract)) {
            return $this->make($abstract);
        }

        throw new ClassNotFoundException('class not exists: ' . $abstract, $abstract);
    }

    /**
     * ��һ���ࡢ�հ���ʵ�����ӿ�ʵ�ֵ�����
     * @access public
     * @param string|array $abstract ���ʶ���ӿ�
     * @param mixed        $concrete Ҫ�󶨵��ࡢ�հ�����ʵ��
     * @return $this
     */
    public function bind($abstract, $concrete = null)
    {
        if (is_array($abstract)) {
            $this->bind = array_merge($this->bind, $abstract);
        } elseif ($concrete instanceof Closure) {
            $this->bind[$abstract] = $concrete;
        } elseif (is_object($concrete)) {
            $this->instance($abstract, $concrete);
        } else {
            $this->bind[$abstract] = $concrete;
        }

        return $this;
    }

    /**
     * ��һ����ʵ��������
     * @access public
     * @param string $abstract �������߱�ʶ
     * @param object $instance ���ʵ��
     * @return $this
     */
    public function instance(string $abstract, $instance)
    {
        if (isset($this->bind[$abstract])) {
            $bind = $this->bind[$abstract];

            if (is_string($bind)) {
                return $this->instance($bind, $instance);
            }
        }

        $this->instances[$abstract] = $instance;

        return $this;
    }

    /**
     * �ж��������Ƿ�����༰��ʶ
     * @access public
     * @param string $abstract �������߱�ʶ
     * @return bool
     */
    public function bound(string $abstract): bool
    {
        return isset($this->bind[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * �ж��������Ƿ�����༰��ʶ
     * @access public
     * @param string $name �������߱�ʶ
     * @return bool
     */
    public function has($name): bool
    {
        return $this->bound($name);
    }

    /**
     * �ж��������Ƿ���ڶ���ʵ��
     * @access public
     * @param string $abstract �������߱�ʶ
     * @return bool
     */
    public function exists(string $abstract): bool
    {
        if (isset($this->bind[$abstract])) {
            $bind = $this->bind[$abstract];

            if (is_string($bind)) {
                return $this->exists($bind);
            }
        }

        return isset($this->instances[$abstract]);
    }

    /**
     * �������ʵ�� �Ѿ�������ֱ�ӻ�ȡ
     * @access public
     * @param string $abstract    �������߱�ʶ
     * @param array  $vars        ����
     * @param bool   $newInstance �Ƿ�ÿ�δ����µ�ʵ��
     * @return mixed
     */
    public function make(string $abstract, array $vars = [], bool $newInstance = false)
    {
        if (isset($this->instances[$abstract]) && !$newInstance) {
            return $this->instances[$abstract];
        }

        if (isset($this->bind[$abstract])) {
            $concrete = $this->bind[$abstract];

            if ($concrete instanceof Closure) {
                $object = $this->invokeFunction($concrete, $vars);
            } else {
                return $this->make($concrete, $vars, $newInstance);
            }
        } else {
            $object = $this->invokeClass($abstract, $vars);
        }

        if (!$newInstance) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * ɾ�������еĶ���ʵ��
     * @access public
     * @param string $name �������߱�ʶ
     * @return void
     */
    public function delete($name)
    {
        if (isset($this->bind[$name])) {
            $bind = $this->bind[$name];

            if (is_string($bind)) {
                return $this->delete($bind);
            }
        }

        if (isset($this->instances[$name])) {
            unset($this->instances[$name]);
        }
    }

    /**
     * ִ�к������߱հ����� ֧�ֲ�������
     * @access public
     * @param mixed $function �������߱հ�
     * @param array $vars     ����
     * @return mixed
     */
    public function invokeFunction($function, array $vars = [])
    {
        try {
            $reflect = new ReflectionFunction($function);

            $args = $this->bindParams($reflect, $vars);

            return call_user_func_array($function, $args);
        } catch (ReflectionException $e) {
            throw new Exception('function not exists: ' . $function . '()');
        }
    }

    /**
     * ���÷���ִ����ķ��� ֧�ֲ�����
     * @access public
     * @param mixed $method ����
     * @param array $vars   ����
     * @return mixed
     */
    public function invokeMethod($method, array $vars = [])
    {
        try {
            if (is_array($method)) {
                $class   = is_object($method[0]) ? $method[0] : $this->invokeClass($method[0]);
                $reflect = new ReflectionMethod($class, $method[1]);
            } else {
                // ��̬����
                $reflect = new ReflectionMethod($method);
            }

            $args = $this->bindParams($reflect, $vars);

            return $reflect->invokeArgs($class ?? null, $args);
        } catch (ReflectionException $e) {
            if (is_array($method)) {
                $class    = is_object($method[0]) ? get_class($method[0]) : $method[0];
                $callback = $class . '::' . $method[1];
            } else {
                $callback = $method;
            }

            throw new Exception('method not exists: ' . $callback . '()');
        }
    }

    /**
     * ���÷���ִ����ķ��� ֧�ֲ�����
     * @access public
     * @param object $instance ����ʵ��
     * @param mixed  $reflect  ������
     * @param array  $vars     ����
     * @return mixed
     */
    public function invokeReflectMethod($instance, $reflect, array $vars = [])
    {
        $args = $this->bindParams($reflect, $vars);

        return $reflect->invokeArgs($instance, $args);
    }

    /**
     * ���÷���ִ��callable ֧�ֲ�����
     * @access public
     * @param mixed $callable
     * @param array $vars ����
     * @return mixed
     */
    public function invoke($callable, array $vars = [])
    {
        if ($callable instanceof Closure) {
            return $this->invokeFunction($callable, $vars);
        }

        return $this->invokeMethod($callable, $vars);
    }

    /**
     * ���÷���ִ�����ʵ���� ֧������ע��
     * @access public
     * @param string $class ����
     * @param array  $vars  ����
     * @return mixed
     */
    public function invokeClass(string $class, array $vars = [])
    {
        try {
            $reflect = new ReflectionClass($class);

            if ($reflect->hasMethod('__make')) {
                $method = new ReflectionMethod($class, '__make');

                if ($method->isPublic() && $method->isStatic()) {
                    $args = $this->bindParams($method, $vars);
                    return $method->invokeArgs(null, $args);
                }
            }

            $constructor = $reflect->getConstructor();

            $args = $constructor ? $this->bindParams($constructor, $vars) : [];

            return $reflect->newInstanceArgs($args);
        } catch (ReflectionException $e) {
            throw new ClassNotFoundException('class not exists: ' . $class, $class);
        }
    }

    /**
     * �󶨲���
     * @access protected
     * @param \ReflectionMethod|\ReflectionFunction $reflect ������
     * @param array                                 $vars    ����
     * @return array
     */
    protected function bindParams($reflect, array $vars = []): array
    {
        if ($reflect->getNumberOfParameters() == 0) {
            return [];
        }

        // �ж��������� ��������ʱ��˳��󶨲���
        reset($vars);
        $type   = key($vars) === 0 ? 1 : 0;
        $params = $reflect->getParameters();
        $args   = [];

        foreach ($params as $param) {
            $name      = $param->getName();
            $lowerName = App::parseName($name);
            $class     = $param->getClass();

            if ($class) {
                $args[] = $this->getObjectParam($class->getName(), $vars);
            } elseif (1 == $type && !empty($vars)) {
                $args[] = array_shift($vars);
            } elseif (0 == $type && isset($vars[$name])) {
                $args[] = $vars[$name];
            } elseif (0 == $type && isset($vars[$lowerName])) {
                $args[] = $vars[$lowerName];
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                throw new InvalidArgumentException('method param miss:' . $name);
            }
        }

        return $args;
    }

    /**
     * ��ȡ�������͵Ĳ���ֵ
     * @access protected
     * @param string $className ����
     * @param array  $vars      ����
     * @return mixed
     */
    protected function getObjectParam(string $className, array &$vars)
    {
        $array = $vars;
        $value = array_shift($array);

        if ($value instanceof $className) {
            $result = $value;
            array_shift($vars);
        } else {
            $result = $this->make($className);
        }

        return $result;
    }

    public function __set($name, $value)
    {
        $this->bind($name, $value);
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    public function __isset($name): bool
    {
        return $this->exists($name);
    }

    public function __unset($name)
    {
        $this->delete($name);
    }

    public function offsetExists($key)
    {
        return $this->exists($key);
    }

    public function offsetGet($key)
    {
        return $this->make($key);
    }

    public function offsetSet($key, $value)
    {
        $this->bind($key, $value);
    }

    public function offsetUnset($key)
    {
        $this->delete($key);
    }

    //Countable
    public function count()
    {
        return count($this->instances);
    }

    //IteratorAggregate
    public function getIterator()
    {
        return new ArrayIterator($this->instances);
    }
}
