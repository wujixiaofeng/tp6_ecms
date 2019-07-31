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

/**
 * PDO�쳣������
 * ���·�װ��ϵͳ��\PDOException��
 */
class PDOException extends DbException
{
    /**
     * PDOException constructor.
     * @access public
     * @param  \PDOException $exception
     * @param  array         $config
     * @param  string        $sql
     * @param  int           $code
     */
    public function __construct(\PDOException $exception, array $config = [], string $sql = '', int $code = 10501)
    {
        $error = $exception->errorInfo;

        $this->setData('PDO Error Info', [
            'SQLSTATE'             => $error[0],
            'Driver Error Code'    => $error[1] ?? 0,
            'Driver Error Message' => $error[2] ?? '',
        ]);

        parent::__construct($exception->getMessage(), $config, $sql, $code);
    }
}
