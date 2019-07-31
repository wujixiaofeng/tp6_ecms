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

namespace think\exception;

use think\Exception;

/**
 * ThinkPHP�����쳣
 * ��Ҫ���ڷ�װ set_error_handler �� register_shutdown_function �õ��Ĵ���
 * ������ think\Exception �̳еĹ���
 * ������PHPϵͳ\ErrorException���ܻ���һ��
 */
class ErrorException extends Exception
{
    /**
     * ���ڱ�����󼶱�
     * @var integer
     */
    protected $severity;

    /**
     * �����쳣���캯��
     * @access public
     * @param  integer $severity ���󼶱�
     * @param  string  $message  ������ϸ��Ϣ
     * @param  string  $file     �����ļ�·��
     * @param  integer $line     �����к�
     */
    public function __construct(int $severity, string $message, string $file, int $line)
    {
        $this->severity = $severity;
        $this->message  = $message;
        $this->file     = $file;
        $this->line     = $line;
        $this->code     = 0;
    }

    /**
     * ��ȡ���󼶱�
     * @access public
     * @return integer ���󼶱�
     */
    final public function getSeverity()
    {
        return $this->severity;
    }
}
