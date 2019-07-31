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

namespace think;

/**
 * �쳣������
 */
class Exception extends \Exception
{
    /**
     * �����쳣ҳ����ʾ�Ķ���Debug����
     * @var array
     */
    protected $data = [];

    /**
     * �����쳣�����Debug����
     * ���ݽ�����ʾΪ����ĸ�ʽ
     *
     * Exception Data
     * --------------------------------------------------
     * Label 1
     *   key1      value1
     *   key2      value2
     * Label 2
     *   key1      value1
     *   key2      value2
     *
     * @access protected
     * @param  string $label ���ݷ��࣬�����쳣ҳ����ʾ
     * @param  array  $data  ��Ҫ��ʾ�����ݣ�����Ϊ��������
     */
    final protected function setData(string $label, array $data)
    {
        $this->data[$label] = $data;
    }

    /**
     * ��ȡ�쳣����Debug����
     * ��Ҫ����������쳣ҳ����ڵ���
     * @access public
     * @return array ��setData���õ�Debug����
     */
    final public function getData()
    {
        return $this->data;
    }

}
