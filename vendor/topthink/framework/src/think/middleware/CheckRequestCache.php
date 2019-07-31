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

namespace think\middleware;

use Closure;
use think\Cache;
use think\Config;
use think\Request;
use think\Response;

/**
 * ���󻺴洦��
 */
class CheckRequestCache
{
    /**
     * �������
     * @var Cache
     */
    protected $cache;

    /**
     * ���ò���
     * @var array
     */
    protected $config = [
        // ���󻺴���� trueΪ�Զ�����
        'request_cache_key'    => true,
        // ���󻺴���Ч��
        'request_cache_expire' => null,
        // ȫ�����󻺴��ų�����
        'request_cache_except' => [],
        // ���󻺴��Tag
        'request_cache_tag'    => '',
    ];

    public function __construct(Cache $cache, Config $config)
    {
        $this->cache  = $cache;
        $this->config = array_merge($this->config, $config->get('route'));
    }

    /**
     * ���õ�ǰ��ַ�����󻺴�
     * @access public
     * @param Request $request
     * @param Closure $next
     * @param mixed   $cache
     * @return Response
     */
    public function handle($request, Closure $next, $cache = null)
    {
        if ($request->isGet() && false !== $cache) {
            $cache = $cache ?: $this->getRequestCache($request);

            if ($cache) {
                if (is_array($cache)) {
                    list($key, $expire, $tag) = $cache;
                } else {
                    $key    = str_replace('|', '/', $request->url());
                    $expire = $cache;
                    $tag    = null;
                }

                if (strtotime($request->server('HTTP_IF_MODIFIED_SINCE', '')) + $expire > $request->server('REQUEST_TIME')) {
                    // ��ȡ����
                    return Response::create()->code(304);
                } elseif ($this->cache->has($key)) {
                    list($content, $header) = $this->cache->get($key);

                    return Response::create($content)->header($header);
                }
            }
        }

        $response = $next($request);

        if (isset($key) && 200 == $response->getCode() && $response->isAllowCache()) {
            $header                  = $response->getHeader();
            $header['Cache-Control'] = 'max-age=' . $expire . ',must-revalidate';
            $header['Last-Modified'] = gmdate('D, d M Y H:i:s') . ' GMT';
            $header['Expires']       = gmdate('D, d M Y H:i:s', time() + $expire) . ' GMT';

            $this->cache->tag($tag)->set($key, [$response->getContent(), $header], $expire);
        }

        return $response;
    }

    /**
     * ��ȡ��ǰ��ַ�����󻺴���Ϣ
     * @access protected
     * @param Request $request
     * @return mixed
     */
    protected function getRequestCache($request)
    {
        $key    = $this->config['request_cache_key'];
        $expire = $this->config['request_cache_expire'];
        $except = $this->config['request_cache_except'];
        $tag    = $this->config['request_cache_tag'];

        if (false === $key) {
            // �رյ�ǰ����
            return;
        }

        foreach ($except as $rule) {
            if (0 === stripos($request->url(), $rule)) {
                return;
            }
        }

        if ($key instanceof \Closure) {
            $key = call_user_func($key);
        } elseif (true === $key) {
            // �Զ����湦��
            $key = '__URL__';
        } elseif (strpos($key, '|')) {
            list($key, $fun) = explode('|', $key);
        }

        // ��������滻
        if (false !== strpos($key, '__')) {
            $key = str_replace(['__APP__', '__CONTROLLER__', '__ACTION__', '__URL__'], [$request->app(), $request->controller(), $request->action(), md5($request->url(true))], $key);
        }

        if (false !== strpos($key, ':')) {
            $param = $request->param();
            foreach ($param as $item => $val) {
                if (is_string($val) && false !== strpos($key, ':' . $item)) {
                    $key = str_replace(':' . $item, $val, $key);
                }
            }
        } elseif (strpos($key, ']')) {
            if ('[' . $request->ext() . ']' == $key) {
                // ����ĳ����׺������
                $key = md5($request->url());
            } else {
                return;
            }
        }

        if (isset($fun)) {
            $key = $fun($key);
        }

        return [$key, $expire, $tag];
    }
}
