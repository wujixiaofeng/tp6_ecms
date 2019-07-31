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

namespace think\facade;

use think\Facade;

/**
 * @see \think\Event
 * @mixin \think\Event
 * @method \think\Event bind(mixed $name, mixed $event = null) static ָ���¼�����
 * @method \think\Event listen(string $event, mixed $listener) static ע���¼�����
 * @method \think\Event listenEvents(array $events) static ����ע���¼�����
 * @method \think\Event observe(mixed $observer) static ע���¼��۲���
 * @method bool hasEvent(string $event) static �ж��¼��Ƿ���ڼ���
 * @method void remove(string $event) static �Ƴ��¼�����
 * @method mixed trigger(string $event, mixed $params = null, bool $once = false) static �����¼�
 */
class Event extends Facade
{
    /**
     * ��ȡ��ǰFacade��Ӧ�����������Ѿ��󶨵����������ʶ��
     * @access protected
     * @return string
     */
    protected static function getFacadeClass()
    {
        return 'event';
    }
}
