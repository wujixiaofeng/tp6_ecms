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

namespace think\route;

use think\App;
use think\Container;
use think\Request;
use think\Response;
use think\Validate;

/**
 * ·�ɵ��Ȼ�����
 */
abstract class Dispatch
{
    /**
     * Ӧ�ö���
     * @var \think\App
     */
    protected $app;

    /**
     * �������
     * @var Request
     */
    protected $request;

    /**
     * ·�ɹ���
     * @var Rule
     */
    protected $rule;

    /**
     * ������Ϣ
     * @var mixed
     */
    protected $dispatch;

    /**
     * ·�ɱ���
     * @var array
     */
    protected $param;

    /**
     * ״̬��
     * @var int
     */
    protected $code;

    /**
     * �Ƿ���д�Сдת��
     * @var bool
     */
    protected $convert;

    public function __construct(Request $request, Rule $rule, $dispatch, array $param = [], int $code = null)
    {
        $this->request  = $request;
        $this->rule     = $rule;
        $this->dispatch = $dispatch;
        $this->param    = $param;
        $this->code     = $code;

        if (isset($param['convert'])) {
            $this->convert = $param['convert'];
        }
    }

    public function init(App $app)
    {
        $this->app = $app;

        // ��¼��ǰ�����·�ɹ���
        $this->request->setRule($this->rule);

        // ��¼·�ɱ���
        $this->request->setRoute($this->param);

        // ִ��·�ɺ��ò���
        $this->doRouteAfter();
    }

    /**
     * ִ��·�ɵ���
     * @access public
     * @return mixed
     */
    public function run(): Response
    {
        $option = $this->rule->getOption();

        // �����Զ���֤
        if (isset($option['validate'])) {
            $this->autoValidate($option['validate']);
        }

        $data = $this->exec();

        return $this->autoResponse($data);
    }

    protected function autoResponse($data): Response
    {
        if ($data instanceof Response) {
            $response = $data;
        } elseif (!is_null($data)) {
            // Ĭ���Զ�ʶ����Ӧ�������
            $type     = $this->request->isJson() ? 'json' : 'html';
            $response = Response::create($data, $type);
        } else {
            $data = ob_get_clean();

            $content  = false === $data ? '' : $data;
            $status   = '' === $content && $this->request->isJson() ? 204 : 200;
            $response = Response::create($content, '', $status);
        }

        return $response;
    }

    /**
     * ���·�ɺ��ò���
     * @access protected
     * @return void
     */
    protected function doRouteAfter(): void
    {
        // ��¼ƥ���·����Ϣ
        $option = $this->rule->getOption();

        // ����м��
        if (!empty($option['middleware'])) {
            $this->app->middleware->import($option['middleware']);
        }

        // ��ģ������
        if (!empty($option['model'])) {
            $this->createBindModel($option['model'], $this->request->route());
        }

        if (!empty($option['append'])) {
            $this->request->setRoute($option['append']);
        }
    }

    /**
     * ·�ɰ�ģ��ʵ��
     * @access protected
     * @param array $bindModel ��ģ��
     * @param array $matches   ·�ɱ���
     * @return void
     */
    protected function createBindModel(array $bindModel, array $matches): void
    {
        foreach ($bindModel as $key => $val) {
            if ($val instanceof \Closure) {
                $result = $this->app->invokeFunction($val, $matches);
            } else {
                $fields = explode('&', $key);

                if (is_array($val)) {
                    list($model, $exception) = $val;
                } else {
                    $model     = $val;
                    $exception = true;
                }

                $where = [];
                $match = true;

                foreach ($fields as $field) {
                    if (!isset($matches[$field])) {
                        $match = false;
                        break;
                    } else {
                        $where[] = [$field, '=', $matches[$field]];
                    }
                }

                if ($match) {
                    $result = $model::where($where)->failException($exception)->find();
                }
            }

            if (!empty($result)) {
                // ע������
                $this->app->instance(get_class($result), $result);
            }
        }
    }

    /**
     * ��֤����
     * @access protected
     * @param array $option
     * @return void
     * @throws \think\exception\ValidateException
     */
    protected function autoValidate(array $option): void
    {
        list($validate, $scene, $message, $batch) = $option;

        if (is_array($validate)) {
            // ָ����֤����
            $v = new Validate();
            $v->rule($validate);
        } else {
            // ������֤��
            /** @var Validate $class */
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);

            $v = new $class();

            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message)->batch($batch)->failException(true)->check($this->request->param());
    }

    public function convert(bool $convert)
    {
        $this->convert = $convert;

        return $this;
    }

    public function getDispatch()
    {
        return $this->dispatch;
    }

    public function getParam(): array
    {
        return $this->param;
    }

    abstract public function exec();

    public function __sleep()
    {
        return ['rule', 'dispatch', 'convert', 'param', 'code', 'controller', 'actionName'];
    }

    public function __wakeup()
    {
        $this->app     = Container::pull('app');
        $this->request = $this->app->request;
    }

    public function __debugInfo()
    {
        return [
            'dispatch' => $this->dispatch,
            'param'    => $this->param,
            'code'     => $this->code,
            'rule'     => $this->rule,
        ];
    }
}
