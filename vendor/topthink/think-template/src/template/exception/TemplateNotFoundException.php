<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace think\template\exception;

class TemplateNotFoundException extends \RuntimeException
{
    protected $template;

    public function __construct(string $message, string $template = '')
    {
        $this->message  = $message;
        $this->template = $template;
    }

    /**
     * ��ȡģ���ļ�
     * @access public
     * @return string
     */
    public function getTemplate(): string
    {
        return $this->template;
    }
}