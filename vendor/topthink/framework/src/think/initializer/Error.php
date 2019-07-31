<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: ����� <zuojiazi@vip.qq.com> <http://zjzit.cn>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace think\initializer;

use think\App;
use think\console\Output as ConsoleOutput;
use think\exception\ErrorException;
use think\exception\Handle;
use Throwable;

/**
 * ������쳣����
 */
class Error
{
    /** @var App */
    protected $app;

    /**
     * ע���쳣����
     * @access public
     * @param App $app
     * @return void
     */
    public function init(App $app)
    {
        $this->app = $app;
        //error_reporting(E_ALL);
        set_error_handler([$this, 'appError']);
        set_exception_handler([$this, 'appException']);
        register_shutdown_function([$this, 'appShutdown']);
    }

    /**
     * Exception Handler
     * @access public
     * @param \Throwable $e
     */
    public function appException(Throwable $e): void
    {
        $handler = $this->getExceptionHandler();

        $handler->report($e);

        if ($this->app->runningInConsole()) {
            $handler->renderForConsole(new ConsoleOutput, $e);
        } else {
            $handler->render($this->app->request, $e)->setCookie($this->app->cookie)->send();
        }
    }

    /**
     * Error Handler
     * @access public
     * @param integer $errno   ������
     * @param string  $errstr  ��ϸ������Ϣ
     * @param string  $errfile ������ļ�
     * @param integer $errline �����к�
     * @throws ErrorException
     */
    public function appError(int $errno, string $errstr, string $errfile = '', int $errline = 0): void
    {
        $exception = new ErrorException($errno, $errstr, $errfile, $errline);

        if (error_reporting() & $errno) {
            // ��������Ϣ�й��� think\exception\ErrorException
            throw $exception;
        }
    }

    /**
     * Shutdown Handler
     * @access public
     */
    public function appShutdown(): void
    {
        if (!is_null($error = error_get_last()) && $this->isFatal($error['type'])) {
            // ��������Ϣ�й���think\ErrorException
            $exception = new ErrorException($error['type'], $error['message'], $error['file'], $error['line']);

            $this->appException($exception);
        }
    }

    /**
     * ȷ�����������Ƿ�����
     *
     * @access protected
     * @param int $type
     * @return bool
     */
    protected function isFatal(int $type): bool
    {
        return in_array($type, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE]);
    }

    /**
     * Get an instance of the exception handler.
     *
     * @access protected
     * @return Handle
     */
    protected function getExceptionHandler()
    {
        return $this->app->make(Handle::class);
    }
}
