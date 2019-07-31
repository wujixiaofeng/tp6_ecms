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

namespace think\contract;

/**
 * ��ͼ�����ӿ�
 */
interface TemplateHandlerInterface
{
    /**
     * ����Ƿ����ģ���ļ�
     * @access public
     * @param  string $template ģ���ļ�����ģ�����
     * @return bool
     */
    public function exists(string $template): bool;

    /**
     * ��Ⱦģ���ļ�
     * @access public
     * @param  string $template ģ���ļ�
     * @param  array  $data ģ�����
     * @return void
     */
    public function fetch(string $template, array $data = []): void;

    /**
     * ��Ⱦģ������
     * @access public
     * @param  string $content ģ������
     * @param  array  $data ģ�����
     * @return void
     */
    public function display(string $content, array $data = []): void;

    /**
     * ����ģ������
     * @access private
     * @param  array $config ����
     * @return void
     */
    public function config(array $config): void;

    /**
     * ��ȡģ����������
     * @access public
     * @param  string $name ������
     * @return void
     */
    public function getConfig(string $name);
}
