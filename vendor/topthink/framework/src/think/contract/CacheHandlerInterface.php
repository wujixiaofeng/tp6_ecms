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

namespace think\contract;

/**
 * ���������ӿ�
 */
interface CacheHandlerInterface
{
    /**
     * �жϻ���
     * @access public
     * @param  string $name ���������
     * @return bool
     */
    public function has($name): bool;

    /**
     * ��ȡ����
     * @access public
     * @param  string $name ���������
     * @param  mixed  $default Ĭ��ֵ
     * @return mixed
     */
    public function get($name, $default = false);

    /**
     * д�뻺��
     * @access public
     * @param  string            $name ���������
     * @param  mixed             $value  �洢����
     * @param  integer|\DateTime $expire  ��Чʱ�䣨�룩
     * @return bool
     */
    public function set($name, $value, $expire = null): bool;

    /**
     * �������棨�����ֵ���棩
     * @access public
     * @param  string $name ���������
     * @param  int    $step ����
     * @return false|int
     */
    public function inc(string $name, int $step = 1);

    /**
     * �Լ����棨�����ֵ���棩
     * @access public
     * @param  string $name ���������
     * @param  int    $step ����
     * @return false|int
     */
    public function dec(string $name, int $step = 1);

    /**
     * ɾ������
     * @access public
     * @param  string $name ���������
     * @return bool
     */
    public function delete($name): bool;

    /**
     * �������
     * @access public
     * @return bool
     */
    public function clear(): bool;

}
