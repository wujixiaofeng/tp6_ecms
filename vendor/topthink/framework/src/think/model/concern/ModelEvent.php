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

namespace think\model\concern;

use think\App;
use think\Container;
use think\exception\ModelEventException;

/**
 * ģ���¼�����
 */
trait ModelEvent
{

    /**
     * �Ƿ���Ҫ�¼���Ӧ
     * @var bool
     */
    protected $withEvent = true;

    /**
     * ��ǰ�������¼���Ӧ
     * @access protected
     * @param  bool $event  �Ƿ���Ҫ�¼���Ӧ
     * @return $this
     */
    public function withEvent(bool $event)
    {
        $this->withEvent = $event;
        return $this;
    }

    /**
     * �����¼�
     * @access protected
     * @param  string $event �¼���
     * @return bool
     */
    protected function trigger(string $event): bool
    {
        if (!$this->withEvent) {
            return true;
        }

        $call = 'on' . App::parseName($event, 1);

        try {
            if (method_exists(static::class, $call)) {
                $result = Container::getInstance()
                    ->invoke([static::class, $call], [$this]);
            } else {
                $result = true;
            }

            return false === $result ? false : true;
        } catch (ModelEventException $e) {
            return false;
        }
    }
}
