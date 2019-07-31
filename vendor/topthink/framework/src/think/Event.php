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

namespace think;

/**
 * �¼�������
 */
class Event
{
    /**
     * ������
     * @var array
     */
    protected $listener = [];

    /**
     * �۲���
     * @var array
     */
    protected $observer = [];

    /**
     * �¼�����
     * @var array
     */
    protected $bind = [
        'AppInit'  => event\AppInit::class,
        'HttpRun'  => event\HttpRun::class,
        'HttpEnd'  => event\HttpEnd::class,
        'LogLevel' => event\LogLevel::class,
        'LogWrite' => event\LogWrite::class,
    ];

    /**
     * �Ƿ���Ҫ�¼���Ӧ
     * @var bool
     */
    protected $withEvent = true;

    /**
     * Ӧ�ö���
     * @var App
     */
    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * �����Ƿ����¼���Ӧ
     * @access protected
     * @param  bool $event �Ƿ���Ҫ�¼���Ӧ
     * @return $this
     */
    public function withEvent(bool $event)
    {
        $this->withEvent = $event;
        return $this;
    }

    /**
     * ����ע���¼�����
     * @access public
     * @param  array $events �¼�����
     * @return $this
     */
    public function listenEvents(array $events)
    {
        if (!$this->withEvent) {
            return $this;
        }

        foreach ($events as $event => $listeners) {
            if (isset($this->bind[$event])) {
                $event = $this->bind[$event];
            }

            $this->listener[$event] = array_merge($this->listener[$event] ?? [], $listeners);
        }

        return $this;
    }

    /**
     * ע���¼�����
     * @access public
     * @param  string $event    �¼�����
     * @param  mixed  $listener ��������������������
     * @param  bool   $first    �Ƿ�����ִ��
     * @return $this
     */
    public function listen(string $event, $listener, bool $first = false)
    {
        if (!$this->withEvent) {
            return $this;
        }

        if (isset($this->bind[$event])) {
            $event = $this->bind[$event];
        }

        if ($first && isset($this->listener[$event])) {
            array_unshift($this->listener[$event], $listener);
        } else {
            $this->listener[$event][] = $listener;
        }

        return $this;
    }

    /**
     * �Ƿ�����¼�����
     * @access public
     * @param  string $event �¼�����
     * @return bool
     */
    public function hasListen(string $event): bool
    {
        if (isset($this->bind[$event])) {
            $event = $this->bind[$event];
        }

        return isset($this->listener[$event]);
    }

    /**
     * �Ƴ��¼�����
     * @access public
     * @param  string $event �¼�����
     * @return $this
     */
    public function remove(string $event): void
    {
        if (isset($this->bind[$event])) {
            $event = $this->bind[$event];
        }

        unset($this->listener[$event]);
    }

    /**
     * ָ���¼�������ʶ ���ڵ���
     * @access public
     * @param  array $events �¼�����
     * @return $this
     */
    public function bind(array $events)
    {
        $this->bind = array_merge($this->bind, $events);

        return $this;
    }

    /**
     * ע���¼�������
     * @access public
     * @param  mixed $subscriber ������
     * @return $this
     */
    public function subscribe($subscriber)
    {
        if (!$this->withEvent) {
            return $this;
        }

        $subscribers = (array) $subscriber;

        foreach ($subscribers as $subscriber) {
            if (is_string($subscriber)) {
                $subscriber = $this->app->make($subscriber);
            }

            if (method_exists($subscriber, 'subscribe')) {
                // �ֶ�����
                $subscriber->subscribe($this);
            } else {
                // ���ܶ���
                $this->observe($subscriber);
            }
        }

        return $this;
    }

    /**
     * �Զ�ע���¼��۲���
     * @access public
     * @param  string|object $observer �۲���
     * @return $this
     */
    public function observe($observer)
    {
        if (!$this->withEvent) {
            return $this;
        }

        if (is_string($observer)) {
            $observer = $this->app->make($observer);
        }

        $events = array_keys($this->listener);

        foreach ($events as $event) {
            $name   = false !== strpos($event, '\\') ? substr(strrchr($event, '\\'), 1) : $event;
            $method = 'on' . $name;

            if (method_exists($observer, $method)) {
                $this->listen($event, [$observer, $method]);
            }
        }

        return $this;
    }

    /**
     * �����¼�
     * @access public
     * @param  string|object $event  �¼�����
     * @param  mixed         $params �������
     * @param  bool          $once   ֻ��ȡһ����Ч����ֵ
     * @return mixed
     */
    public function trigger($event, $params = null, bool $once = false)
    {
        if (!$this->withEvent) {
            return;
        }

        if (is_object($event)) {
            $params = $event;
            $event  = get_class($event);
        }

        if (isset($this->bind[$event])) {
            $event = $this->bind[$event];
        }

        $result    = [];
        $listeners = $this->listener[$event] ?? [];

        foreach ($listeners as $key => $listener) {
            $result[$key] = $this->dispatch($listener, $params);

            if (false === $result[$key] || (!is_null($result[$key]) && $once)) {
                break;
            }
        }

        return $once ? end($result) : $result;
    }

    /**
     * ִ���¼�����
     * @access protected
     * @param  mixed $event  �¼�����
     * @param  mixed $params ����
     * @return mixed
     */
    protected function dispatch($event, $params = null)
    {
        if (!is_string($event)) {
            $call = $event;
        } elseif (strpos($event, '::')) {
            $call = $event;
        } else {
            $obj  = $this->app->make($event);
            $call = [$obj, 'handle'];
        }

        return $this->app->invoke($call, [$params]);
    }

}
