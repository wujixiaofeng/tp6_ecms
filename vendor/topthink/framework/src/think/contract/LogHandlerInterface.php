<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace think\contract;

/**
 * ��־�����ӿ�
 */
interface LogHandlerInterface
{
    /**
     * ��־д��ӿ�
     * @access public
     * @param  array $log ��־��Ϣ
     * @return bool
     */
    public function save(array $log): bool;

}
