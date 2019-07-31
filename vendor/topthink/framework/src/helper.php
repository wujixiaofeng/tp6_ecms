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

//------------------------
// ThinkPHP ���ֺ���
//-------------------------

use think\App;
use think\Container;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Cookie;
use think\facade\Env;
use think\facade\Event;
use think\facade\Lang;
use think\facade\Log;
use think\facade\Request;
use think\facade\Route;
use think\facade\Session;
use think\Response;
use think\route\Url as UrlBuild;
use think\Validate;

if (!function_exists('abort')) {
    /**
     * �׳�HTTP�쳣
     * @param integer|Response $code    ״̬�� ���� Response����ʵ��
     * @param string           $message ������Ϣ
     * @param array            $header  ����
     */
    function abort($code, string $message = null, array $header = [])
    {
        if ($code instanceof Response) {
            throw new HttpResponseException($code);
        } else {
            throw new HttpException($code, $message, null, $header);
        }
    }
}

if (!function_exists('app')) {
    /**
     * ���ٻ�ȡ�����е�ʵ�� ֧������ע��
     * @param string $name        �������ʶ Ĭ�ϻ�ȡ��ǰӦ��ʵ��
     * @param array  $args        ����
     * @param bool   $newInstance �Ƿ�ÿ�δ����µ�ʵ��
     * @return object|App
     */
    function app(string $name = '', array $args = [], bool $newInstance = false)
    {
        return Container::getInstance()->make($name ?: App::class, $args, $newInstance);
    }
}

if (!function_exists('bind')) {
    /**
     * ��һ���ൽ����
     * @param  string|array $abstract ���ʶ���ӿڣ�֧�������󶨣�
     * @param  mixed        $concrete Ҫ�󶨵��ࡢ�հ�����ʵ��
     * @return Container
     */
    function bind($abstract, $concrete = null)
    {
        return Container::getInstance()->bind($abstract, $concrete);
    }
}

if (!function_exists('cache')) {
    /**
     * �������
     * @param  mixed  $name    �������ƣ����Ϊ�����ʾ���л�������
     * @param  mixed  $value   ����ֵ
     * @param  mixed  $options �������
     * @param  string $tag     �����ǩ
     * @return mixed
     */
    function cache($name, $value = '', $options = null, $tag = null)
    {
        if (is_array($name)) {
            // �����ʼ��
            return Cache::connect($name);
        }

        if ('' === $value) {
            // ��ȡ����
            return 0 === strpos($name, '?') ? Cache::has(substr($name, 1)) : Cache::get($name);
        } elseif (is_null($value)) {
            // ɾ������
            return Cache::delete($name);
        }

        // ��������
        if (is_array($options)) {
            $expire = $options['expire'] ?? null; //�޸���ѯ�����޷����ù���ʱ��
        } else {
            $expire = $options;
        }

        if (is_null($tag)) {
            return Cache::set($name, $value, $expire);
        } else {
            return Cache::tag($tag)->set($name, $value, $expire);
        }
    }
}

if (!function_exists('class_basename')) {
    /**
     * ��ȡ����(�����������ռ�)
     *
     * @param  mixed $class ����
     * @return string
     */
    function class_basename($class): string
    {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }
}

if (!function_exists('class_uses_recursive')) {
    /**
     *��ȡһ�����������õ���trait�����������
     *
     * @param  mixed $class ����
     * @return array
     */
    function class_uses_recursive($class): array
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        $results = [];
        $classes = array_merge([$class => $class], class_parents($class));
        foreach ($classes as $class) {
            $results += trait_uses_recursive($class);
        }

        return array_unique($results);
    }
}

if (!function_exists('config')) {
    /**
     * ��ȡ���������ò���
     * @param  string|array $name  ������
     * @param  mixed        $value ����ֵ
     * @return mixed
     */
    function config($name = '', $value = null)
    {
        if (is_array($name)) {
            return Config::set($name, $value);
        }

        return 0 === strpos($name, '?') ? Config::has(substr($name, 1)) : Config::get($name, $value);
    }
}

if (!function_exists('cookie')) {
    /**
     * Cookie����
     * @param  string $name   cookie����
     * @param  mixed  $value  cookieֵ
     * @param  mixed  $option ����
     * @return mixed
     */
    function cookie(string $name, $value = '', $option = null)
    {
        if (is_null($value)) {
            // ɾ��
            Cookie::delete($name);
        } elseif ('' === $value) {
            // ��ȡ
            return 0 === strpos($name, '?') ? Cookie::has(substr($name, 1)) : Cookie::get($name);
        } else {
            // ����
            return Cookie::set($name, $value, $option);
        }
    }
}

if (!function_exists('download')) {
    /**
     * ��ȡ\think\response\Download����ʵ��
     * @param  string $filename Ҫ���ص��ļ�
     * @param  string $name     ��ʾ�ļ���
     * @param  bool   $content  �Ƿ�Ϊ����
     * @param  int    $expire   ��Ч�ڣ��룩
     * @return \think\response\File
     */
    function download(string $filename, string $name = '', bool $content = false, int $expire = 180)
    {
        return Response::create($filename, 'file')->name($name)->isContent($content)->expire($expire);
    }
}

if (!function_exists('dump')) {
    /**
     * ������Ѻõı������
     * @param  mixed  $vars Ҫ����ı���
     * @return void
     */
    function dump(...$vars)
    {
        ob_start();
        var_dump(...$vars);

        $output = ob_get_clean();
        $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);

        if (PHP_SAPI == 'cli') {
            $output = PHP_EOL . $output . PHP_EOL;
        } else {
            if (!extension_loaded('xdebug')) {
                $output = htmlspecialchars($output, ENT_SUBSTITUTE);
            }
            $output = '<pre>' . $output . '</pre>';
        }

        echo $output;
    }
}

if (!function_exists('env')) {
    /**
     * ��ȡ��������ֵ
     * @access public
     * @param  string $name    ������������֧�ֶ��� .�ŷָ
     * @param  string $default Ĭ��ֵ
     * @return mixed
     */
    function env(string $name = null, $default = null)
    {
        return Env::get($name, $default);
    }
}

if (!function_exists('event')) {
    /**
     * �����¼�
     * @param  mixed $event �¼���������������
     * @param  mixed $args  ����
     * @return mixed
     */
    function event($event, $args = null)
    {
        return Event::trigger($event, $args);
    }
}

if (!function_exists('halt')) {
    /**
     * ���Ա��������ж����
     * @param mixed $vars ���Ա���������Ϣ
     */
    function halt(...$vars)
    {
        dump(...$vars);

        throw new HttpResponseException(new Response);
    }
}

if (!function_exists('input')) {
    /**
     * ��ȡ�������� ֧��Ĭ��ֵ�͹���
     * @param  string $key     ��ȡ�ı�����
     * @param  mixed  $default Ĭ��ֵ
     * @param  string $filter  ���˷���
     * @return mixed
     */
    function input(string $key = '', $default = null, $filter = '')
    {
        if (0 === strpos($key, '?')) {
            $key = substr($key, 1);
            $has = true;
        }

        if ($pos = strpos($key, '.')) {
            // ָ��������Դ
            $method = substr($key, 0, $pos);
            if (in_array($method, ['get', 'post', 'put', 'patch', 'delete', 'route', 'param', 'request', 'session', 'cookie', 'server', 'env', 'path', 'file'])) {
                $key = substr($key, $pos + 1);
                if ('server' == $method && is_null($default)) {
                    $default = '';
                }
            } else {
                $method = 'param';
            }
        } else {
            // Ĭ��Ϊ�Զ��ж�
            $method = 'param';
        }

        return isset($has) ?
        request()->has($key, $method) :
        request()->$method($key, $default, $filter);
    }
}

if (!function_exists('invoke')) {
    /**
     * ���÷���ʵ�����������ִ�з��� ֧������ע��
     * @param  mixed $call ��������callable
     * @param  array $args ����
     * @return mixed
     */
    function invoke($call, array $args = [])
    {
        if (is_callable($call)) {
            return Container::getInstance()->invoke($call, $args);
        }

        return Container::getInstance()->invokeClass($call, $args);
    }
}

if (!function_exists('json')) {
    /**
     * ��ȡ\think\response\Json����ʵ��
     * @param  mixed $data    ���ص�����
     * @param  int   $code    ״̬��
     * @param  array $header  ͷ��
     * @param  array $options ����
     * @return \think\response\Json
     */
    function json($data = [], $code = 200, $header = [], $options = [])
    {
        return Response::create($data, 'json', $code)->header($header)->options($options);
    }
}

if (!function_exists('jsonp')) {
    /**
     * ��ȡ\think\response\Jsonp����ʵ��
     * @param  mixed $data    ���ص�����
     * @param  int   $code    ״̬��
     * @param  array $header  ͷ��
     * @param  array $options ����
     * @return \think\response\Jsonp
     */
    function jsonp($data = [], $code = 200, $header = [], $options = [])
    {
        return Response::create($data, 'jsonp', $code)->header($header)->options($options);
    }
}

if (!function_exists('lang')) {
    /**
     * ��ȡ���Ա���ֵ
     * @param  string $name ���Ա�����
     * @param  array  $vars ��̬����ֵ
     * @param  string $lang ����
     * @return mixed
     */
    function lang(string $name, array $vars = [], string $lang = '')
    {
        return Lang::get($name, $vars, $lang);
    }
}

if (!function_exists('parse_name')) {
    /**
     * �ַ����������ת��
     * type 0 ��Java���ת��ΪC�ķ�� 1 ��C���ת��ΪJava�ķ��
     * @param  string $name    �ַ���
     * @param  int    $type    ת������
     * @param  bool   $ucfirst ����ĸ�Ƿ��д���շ����
     * @return string
     */
    function parse_name(string $name, int $type = 0, bool $ucfirst = true): string
    {
        if ($type) {
            $name = preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                return strtoupper($match[1]);
            }, $name);

            return $ucfirst ? ucfirst($name) : lcfirst($name);
        }

        return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
    }
}

if (!function_exists('redirect')) {
    /**
     * ��ȡ\think\response\Redirect����ʵ��
     * @param  mixed         $url    �ض����ַ ֧��Url::build�����ĵ�ַ
     * @param  array|integer $params �������
     * @param  int           $code   ״̬��
     * @return \think\response\Redirect
     */
    function redirect($url = [], $params = [], $code = 302)
    {
        if (is_integer($params)) {
            $code   = $params;
            $params = [];
        }

        return Response::create($url, 'redirect', $code)->params($params);
    }
}

if (!function_exists('request')) {
    /**
     * ��ȡ��ǰRequest����ʵ��
     * @return Request
     */
    function request()
    {
        return app('request');
    }
}

if (!function_exists('response')) {
    /**
     * ������ͨ Response ����ʵ��
     * @param  mixed      $data   �������
     * @param  int|string $code   ״̬��
     * @param  array      $header ͷ��Ϣ
     * @param  string     $type
     * @return Response
     */
    function response($data = '', $code = 200, $header = [], $type = 'html')
    {
        return Response::create($data, $type, $code)->header($header);
    }
}

if (!function_exists('session')) {
    /**
     * Session����
     * @param  string $name  session����
     * @param  mixed  $value sessionֵ
     * @return mixed
     */
    function session(string $name = null, $value = '')
    {
        if (is_null($name)) {
            // ���
            Session::clear();
        } elseif (is_null($value)) {
            // ɾ��
            Session::delete($name);
        } elseif ('' === $value) {
            // �жϻ��ȡ
            return 0 === strpos($name, '?') ? Session::has(substr($name, 1)) : Session::get($name);
        } else {
            // ����
            Session::set($name, $value);
        }
    }
}

if (!function_exists('token')) {
    /**
     * ��ȡToken����
     * @param  string $name ��������
     * @param  mixed  $type �������ɷ���
     * @return string
     */
    function token(string $name = '__token__', string $type = 'md5'): string
    {
        return Request::buildToken($name, $type);
    }
}

if (!function_exists('token_field')) {
    /**
     * �����������ر�
     * @param  string $name ��������
     * @param  mixed  $type �������ɷ���
     * @return string
     */
    function token_field(string $name = '__token__', string $type = 'md5'): string
    {
        $token = Request::buildToken($name, $type);

        return '<input type="hidden" name="' . $name . '" value="' . $token . '" />';
    }
}

if (!function_exists('token_meta')) {
    /**
     * ��������meta
     * @param  string $name ��������
     * @param  mixed  $type �������ɷ���
     * @return string
     */
    function token_meta(string $name = '__token__', string $type = 'md5'): string
    {
        $token = Request::buildToken($name, $type);

        return '<meta name="csrf-token" content="' . $token . '">';
    }
}

if (!function_exists('trace')) {
    /**
     * ��¼��־��Ϣ
     * @param  mixed  $log   log��Ϣ ֧���ַ���������
     * @param  string $level ��־����
     * @return array|void
     */
    function trace($log = '[think]', string $level = 'log')
    {
        if ('[think]' === $log) {
            return Log::getLog();
        }

        Log::record($log, $level);
    }
}

if (!function_exists('trait_uses_recursive')) {
    /**
     * ��ȡһ��trait���������õ���trait
     *
     * @param  string $trait Trait
     * @return array
     */
    function trait_uses_recursive(string $trait): array
    {
        $traits = class_uses($trait);
        foreach ($traits as $trait) {
            $traits += trait_uses_recursive($trait);
        }

        return $traits;
    }
}

if (!function_exists('url')) {
    /**
     * Url����
     * @param string      $url    ·�ɵ�ַ
     * @param array       $vars   ����
     * @param bool|string $suffix ���ɵ�URL��׺
     * @param bool|string $domain ����
     * @return UrlBuild
     */
    function url(string $url = '', array $vars = [], $suffix = true, $domain = false): UrlBuild
    {
        return Route::buildUrl($url, $vars)->suffix($suffix)->domain($domain);
    }
}

if (!function_exists('validate')) {
    /**
     * ������֤����
     * @param  string|array $validate ��֤������������֤��������
     * @param  array        $message  ������ʾ��Ϣ
     * @param  bool         $batch    �Ƿ�������֤
     * @return Validate
     */
    function validate($validate = '', array $message = [], bool $batch = false): Validate
    {
        if (is_array($validate) || '' === $validate) {
            $v = new Validate();
            if (is_array($validate)) {
                $v->rule($validate);
            }
        } else {
            if (strpos($validate, '.')) {
                // ֧�ֳ���
                list($validate, $scene) = explode('.', $validate);
            }

            $class = false !== strpos($validate, '\\') ? $validate : app()->parseClass('validate', $validate);

            $v = new $class();

            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        return $v->message($message)->batch($batch)->failException(true);
    }
}

if (!function_exists('view')) {
    /**
     * ��Ⱦģ�����
     * @param string    $template ģ���ļ�
     * @param array     $vars ģ�����
     * @param int       $code ״̬��
     * @param callable  $filter ���ݹ���
     * @return \think\response\View
     */
    function view(string $template = '', $vars = [], $code = 200, $filter = null)
    {
        return Response::create($template, 'view', $code)->assign($vars)->filter($filter);
    }
}

if (!function_exists('display')) {
    /**
     * ��Ⱦģ�����
     * @param string    $content ��Ⱦ����
     * @param array     $vars ģ�����
     * @param int       $code ״̬��
     * @param callable  $filter ���ݹ���
     * @return \think\response\View
     */
    function display(string $content, $vars = [], $code = 200, $filter = null)
    {
        return Response::create($template, 'view', $code)->isContent(true)->assign($vars)->filter($filter);
    }
}

if (!function_exists('xml')) {
    /**
     * ��ȡ\think\response\Xml����ʵ��
     * @param  mixed $data    ���ص�����
     * @param  int   $code    ״̬��
     * @param  array $header  ͷ��
     * @param  array $options ����
     * @return \think\response\Xml
     */
    function xml($data = [], $code = 200, $header = [], $options = [])
    {
        return Response::create($data, 'xml', $code)->header($header)->options($options);
    }
}
