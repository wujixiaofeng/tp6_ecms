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

namespace think\model;

use think\Model;

/**
 * ��Զ��м��ģ����
 */
class Pivot extends Model
{

    /**
     * ��ģ��
     * @var Model
     */
    public $parent;

    /**
     * �Ƿ�ʱ���Զ�д��
     * @var bool
     */
    protected $autoWriteTimestamp = false;

    /**
     * �ܹ�����
     * @access public
     * @param  array  $data ����
     * @param  Model  $parent �ϼ�ģ��
     * @param  string $table �м����ݱ���
     */
    public function __construct(array $data = [], Model $parent = null, string $table = '')
    {
        $this->parent = $parent;

        if (is_null($this->name)) {
            $this->name = $table;
        }

        parent::__construct($data);
    }

}
