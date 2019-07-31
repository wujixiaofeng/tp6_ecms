<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

namespace think\exception;

use Psr\Container\NotFoundExceptionInterface;

/**
 * �಻�����쳣
 */
class ClassNotFoundException extends \RuntimeException implements NotFoundExceptionInterface
{
    protected $class;
    public function __construct(string $message, string $class = '')
    {
        $this->message = $message;
        $this->class   = $class;
    }

    /**
     * ��ȡ����
     * @access public
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }
}
