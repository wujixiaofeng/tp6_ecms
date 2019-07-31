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

namespace think\route\dispatch;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use think\App;
use think\exception\ClassNotFoundException;
use think\exception\HttpException;
use think\Request;
use think\route\Dispatch;

/**
 * Controller Dispatcher
 */
class Controller extends Dispatch
{
    /**
     * ��������
     * @var string
     */
    protected $controller;

    /**
     * ������
     * @var string
     */
    protected $actionName;

    public function init(App $app)
    {
        parent::init($app);

        $result = $this->dispatch;

        if (is_string($result)) {
            $result = explode('/', $result);
        }

        // �Ƿ��Զ�ת���������Ͳ�����
        $convert = is_bool($this->convert) ? $this->convert : $this->rule->config('url_convert');
        // ��ȡ��������
        $controller = strip_tags($result[0] ?: $this->rule->config('default_controller'));

        $this->controller = $convert ? strtolower($controller) : $controller;

        // ��ȡ������
        $this->actionName = strip_tags($result[1] ?: $this->rule->config('default_action'));

        // ���õ�ǰ����Ŀ�����������
        $this->request
            ->setController(App::parseName($this->controller, 1))
            ->setAction($this->actionName);
    }

    public function exec()
    {
        try {
            // ʵ����������
            $instance = $this->controller($this->controller);
        } catch (ClassNotFoundException $e) {
            throw new HttpException(404, 'controller not exists:' . $e->getClass());
        }

        // ע��������м��
        $this->registerControllerMiddleware($instance);

        $this->app->middleware->controller(function (Request $request, $next) use ($instance) {
            // ��ȡ��ǰ������
            $action = $this->actionName . $this->rule->config('action_suffix');

            if (is_callable([$instance, $action])) {
                // �Զ���ȡ�������
                $vars = array_merge($this->request->param(), $this->param);

                try {
                    $reflect = new ReflectionMethod($instance, $action);
                    // �ϸ��ȡ��ǰ����������
                    $actionName = $reflect->getName();
                    $this->request->setAction($actionName);
                } catch (ReflectionException $e) {
                    $reflect = new ReflectionMethod($instance, '__call');
                    $vars    = [$action, $vars];
                    $this->request->setAction($action);
                }
            } else {
                // ����������
                throw new HttpException(404, 'method not exists:' . get_class($instance) . '->' . $action . '()');
            }

            $data = $this->app->invokeReflectMethod($instance, $reflect, $vars);

            return $this->autoResponse($data);
        });

        return $this->app->middleware->dispatch($this->request, 'controller');
    }

    /**
     * ʹ�÷������ע��������м��
     * @access public
     * @param  object $controller ������ʵ��
     * @return void
     */
    protected function registerControllerMiddleware($controller): void
    {
        $class = new ReflectionClass($controller);

        if ($class->hasProperty('middleware')) {
            $reflectionProperty = $class->getProperty('middleware');
            $reflectionProperty->setAccessible(true);

            $middlewares = $reflectionProperty->getValue($controller);

            foreach ($middlewares as $key => $val) {
                if (!is_int($key)) {
                    if (isset($val['only']) && !in_array($this->request->action(true), array_map(function ($item) {
                        return strtolower($item);
                    }, $val['only']))) {
                        continue;
                    } elseif (isset($val['except']) && in_array($this->request->action(true), array_map(function ($item) {
                        return strtolower($item);
                    }, $val['except']))) {
                        continue;
                    } else {
                        $val = $key;
                    }
                }

                $this->app->middleware->controller($val);
            }
        }
    }

    /**
     * ʵ�������ʿ�����
     * @access public
     * @param  string $name ��Դ��ַ
     * @return object
     * @throws ClassNotFoundException
     */
    public function controller(string $name)
    {
        $suffix = $this->rule->config('controller_suffix') ? 'Controller' : '';

        $controllerLayer = $this->rule->config('controller_layer') ?: 'controller';
        $emptyController = $this->rule->config('empty_controller') ?: 'Error';

        $class = $this->app->parseClass($controllerLayer, $name . $suffix);

        if (class_exists($class)) {
            return $this->app->make($class, [], true);
        } elseif ($emptyController && class_exists($emptyClass = $this->app->parseClass($controllerLayer, $emptyController . $suffix))) {
            return $this->app->make($emptyClass, [], true);
        }

        throw new ClassNotFoundException('class not exists:' . $class, $class);
    }
}
