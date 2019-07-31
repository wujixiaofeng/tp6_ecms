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

use Closure;
use think\exception\ValidateException;
use think\validate\ValidateRule;

/**
 * ������֤��
 */
class Validate
{
    /**
     * �Զ�����֤����
     * @var array
     */
    protected $type = [];

    /**
     * ��֤���ͱ���
     * @var array
     */
    protected $alias = [
        '>' => 'gt', '>=' => 'egt', '<' => 'lt', '<=' => 'elt', '=' => 'eq', 'same' => 'eq',
    ];

    /**
     * ��ǰ��֤����
     * @var array
     */
    protected $rule = [];

    /**
     * ��֤��ʾ��Ϣ
     * @var array
     */
    protected $message = [];

    /**
     * ��֤�ֶ�����
     * @var array
     */
    protected $field = [];

    /**
     * Ĭ�Ϲ�����ʾ
     * @var array
     */
    protected $typeMsg = [
        'require'     => ':attribute require',
        'must'        => ':attribute must',
        'number'      => ':attribute must be numeric',
        'integer'     => ':attribute must be integer',
        'float'       => ':attribute must be float',
        'boolean'     => ':attribute must be bool',
        'email'       => ':attribute not a valid email address',
        'mobile'      => ':attribute not a valid mobile',
        'array'       => ':attribute must be a array',
        'accepted'    => ':attribute must be yes,on or 1',
        'date'        => ':attribute not a valid datetime',
        'file'        => ':attribute not a valid file',
        'image'       => ':attribute not a valid image',
        'alpha'       => ':attribute must be alpha',
        'alphaNum'    => ':attribute must be alpha-numeric',
        'alphaDash'   => ':attribute must be alpha-numeric, dash, underscore',
        'activeUrl'   => ':attribute not a valid domain or ip',
        'chs'         => ':attribute must be chinese',
        'chsAlpha'    => ':attribute must be chinese or alpha',
        'chsAlphaNum' => ':attribute must be chinese,alpha-numeric',
        'chsDash'     => ':attribute must be chinese,alpha-numeric,underscore, dash',
        'url'         => ':attribute not a valid url',
        'ip'          => ':attribute not a valid ip',
        'dateFormat'  => ':attribute must be dateFormat of :rule',
        'in'          => ':attribute must be in :rule',
        'notIn'       => ':attribute be notin :rule',
        'between'     => ':attribute must between :1 - :2',
        'notBetween'  => ':attribute not between :1 - :2',
        'length'      => 'size of :attribute must be :rule',
        'max'         => 'max size of :attribute must be :rule',
        'min'         => 'min size of :attribute must be :rule',
        'after'       => ':attribute cannot be less than :rule',
        'before'      => ':attribute cannot exceed :rule',
        'expire'      => ':attribute not within :rule',
        'allowIp'     => 'access IP is not allowed',
        'denyIp'      => 'access IP denied',
        'confirm'     => ':attribute out of accord with :2',
        'different'   => ':attribute cannot be same with :2',
        'egt'         => ':attribute must greater than or equal :rule',
        'gt'          => ':attribute must greater than :rule',
        'elt'         => ':attribute must less than or equal :rule',
        'lt'          => ':attribute must less than :rule',
        'eq'          => ':attribute must equal :rule',
        'unique'      => ':attribute has exists',
        'regex'       => ':attribute not conform to the rules',
        'method'      => 'invalid Request method',
        'token'       => 'invalid token',
        'fileSize'    => 'filesize not match',
        'fileExt'     => 'extensions to upload is not allowed',
        'fileMime'    => 'mimetype to upload is not allowed',
    ];

    /**
     * ��ǰ��֤����
     * @var string
     */
    protected $currentScene;

    /**
     * ����������֤����
     * @var array
     */
    protected $defaultRegex = [
        'alpha'       => '/^[A-Za-z]+$/',
        'alphaNum'    => '/^[A-Za-z0-9]+$/',
        'alphaDash'   => '/^[A-Za-z0-9\-\_]+$/',
        'chs'         => '/^[\x{4e00}-\x{9fa5}]+$/u',
        'chsAlpha'    => '/^[\x{4e00}-\x{9fa5}a-zA-Z]+$/u',
        'chsAlphaNum' => '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9]+$/u',
        'chsDash'     => '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9\_\-]+$/u',
        'mobile'      => '/^1[3-9][0-9]\d{8}$/',
        'idCard'      => '/(^[1-9]\d{5}(18|19|([23]\d))\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$)|(^[1-9]\d{5}\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{2}$)/',
        'zip'         => '/\d{6}/',
    ];

    /**
     * Filter_var ����
     * @var array
     */
    protected $filter = [
        'email'   => FILTER_VALIDATE_EMAIL,
        'ip'      => [FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6],
        'integer' => FILTER_VALIDATE_INT,
        'url'     => FILTER_VALIDATE_URL,
        'macAddr' => FILTER_VALIDATE_MAC,
        'float'   => FILTER_VALIDATE_FLOAT,
    ];

    /**
     * ��֤��������
     * @var array
     */
    protected $scene = [];

    /**
     * ��֤ʧ�ܴ�����Ϣ
     * @var array
     */
    protected $error = [];

    /**
     * �Ƿ�������֤
     * @var bool
     */
    protected $batch = false;

    /**
     * ��֤ʧ���Ƿ��׳��쳣
     * @var bool
     */
    protected $failException = false;

    /**
     * ������Ҫ��֤�Ĺ���
     * @var array
     */
    protected $only = [];

    /**
     * ������Ҫ�Ƴ�����֤����
     * @var array
     */
    protected $remove = [];

    /**
     * ������Ҫ׷�ӵ���֤����
     * @var array
     */
    protected $append = [];

    /**
     * ��֤������
     * @var array
     */
    protected $regex = [];

    /**
     * Db����
     * @var Db
     */
    protected $db;

    /**
     * ���Զ���
     * @var Lang
     */
    protected $lang;

    /**
     * �������
     * @var Request
     */
    protected $request;

    /**
     * @var Closure
     */
    protected static $maker = [];

    /**
     * ���췽��
     * @access public
     */
    public function __construct()
    {
        if (!empty(static::$maker)) {
            foreach (static::$maker as $maker) {
                call_user_func($maker, $this);
            }
        }
    }

    /**
     * ���÷���ע��
     * @access public
     * @param  Closure $maker
     * @return void
     */
    public static function maker(Closure $maker)
    {
        static::$maker[] = $maker;
    }

    /**
     * ����Lang����
     * @access public
     * @param  Lang $lang Lang����
     * @return void
     */
    public function setLang(Lang $lang)
    {
        $this->lang = $lang;
    }

    /**
     * ����Db����
     * @access public
     * @param  Db $db Db����
     * @return void
     */
    public function setDb(Db $db)
    {
        $this->db = $db;
    }

    /**
     * ����Request����
     * @access public
     * @param  Request $request Request����
     * @return void
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * ����ֶ���֤����
     * @access protected
     * @param  string|array  $name  �ֶ����ƻ��߹�������
     * @param  mixed         $rule  ��֤��������ֶ�������Ϣ
     * @return $this
     */
    public function rule($name, $rule = '')
    {
        if (is_array($name)) {
            $this->rule = $name + $this->rule;
            if (is_array($rule)) {
                $this->field = array_merge($this->field, $rule);
            }
        } else {
            $this->rule[$name] = $rule;
        }

        return $this;
    }

    /**
     * ע����֤�����ͣ�����
     * @access public
     * @param  string   $type  ��֤��������
     * @param  callable $callback callback����(��հ�)
     * @param  string   $message  ��֤ʧ����ʾ��Ϣ
     * @return $this
     */
    public function extend(string $type, callable $callback = null, string $message = null)
    {
        $this->type[$type] = $callback;

        if ($message) {
            $this->typeMsg[$type] = $message;
        }

        return $this;
    }

    /**
     * ������֤�����Ĭ����ʾ��Ϣ
     * @access public
     * @param  string|array $type  ��֤�����������ƻ�������
     * @param  string       $msg  ��֤��ʾ��Ϣ
     * @return void
     */
    public function setTypeMsg($type, string $msg = null): void
    {
        if (is_array($type)) {
            $this->typeMsg = array_merge($this->typeMsg, $type);
        } else {
            $this->typeMsg[$type] = $msg;
        }
    }

    /**
     * ������ʾ��Ϣ
     * @access public
     * @param  array $message ������Ϣ
     * @return Validate
     */
    public function message(array $message)
    {
        $this->message = array_merge($this->message, $message);

        return $this;
    }

    /**
     * ������֤����
     * @access public
     * @param  string $name  ������
     * @return $this
     */
    public function scene(string $name)
    {
        // ���õ�ǰ����
        $this->currentScene = $name;

        return $this;
    }

    /**
     * �ж��Ƿ����ĳ����֤����
     * @access public
     * @param  string $name ������
     * @return bool
     */
    public function hasScene(string $name): bool
    {
        return isset($this->scene[$name]) || method_exists($this, 'scene' . $name);
    }

    /**
     * ����������֤
     * @access public
     * @param  bool $batch  �Ƿ�������֤
     * @return $this
     */
    public function batch(bool $batch = true)
    {
        $this->batch = $batch;

        return $this;
    }

    /**
     * ������֤ʧ�ܺ��Ƿ��׳��쳣
     * @access protected
     * @param  bool $fail �Ƿ��׳��쳣
     * @return $this
     */
    public function failException(bool $fail = true)
    {
        $this->failException = $fail;

        return $this;
    }

    /**
     * ָ����Ҫ��֤���ֶ��б�
     * @access public
     * @param  array $fields  �ֶ���
     * @return $this
     */
    public function only(array $fields)
    {
        $this->only = $fields;

        return $this;
    }

    /**
     * �Ƴ�ĳ���ֶε���֤����
     * @access public
     * @param  string|array $field  �ֶ���
     * @param  mixed        $rule   ��֤���� true �Ƴ����й���
     * @return $this
     */
    public function remove($field, $rule = null)
    {
        if (is_array($field)) {
            foreach ($field as $key => $rule) {
                if (is_int($key)) {
                    $this->remove($rule);
                } else {
                    $this->remove($key, $rule);
                }
            }
        } else {
            if (is_string($rule)) {
                $rule = explode('|', $rule);
            }

            $this->remove[$field] = $rule;
        }

        return $this;
    }

    /**
     * ׷��ĳ���ֶε���֤����
     * @access public
     * @param  string|array $field  �ֶ���
     * @param  mixed        $rule   ��֤����
     * @return $this
     */
    public function append($field, $rule = null)
    {
        if (is_array($field)) {
            foreach ($field as $key => $rule) {
                $this->append($key, $rule);
            }
        } else {
            if (is_string($rule)) {
                $rule = explode('|', $rule);
            }

            $this->append[$field] = $rule;
        }

        return $this;
    }

    /**
     * �����Զ���֤
     * @access public
     * @param  array $data  ����
     * @param  array $rules  ��֤����
     * @return bool
     */
    public function check(array $data, array $rules = []): bool
    {
        $this->error = [];

        if (empty($rules)) {
            // ��ȡ��֤����
            $rules = $this->rule;
        }

        if ($this->currentScene) {
            $this->getScene($this->currentScene);
        }

        foreach ($this->append as $key => $rule) {
            if (!isset($rules[$key])) {
                $rules[$key] = $rule;
            }
        }

        foreach ($rules as $key => $rule) {
            // field => 'rule1|rule2...' field => ['rule1','rule2',...]
            if (strpos($key, '|')) {
                // �ֶ�|���� ����ָ����������
                list($key, $title) = explode('|', $key);
            } else {
                $title = $this->field[$key] ?? $key;
            }

            // �������
            if (!empty($this->only) && !in_array($key, $this->only)) {
                continue;
            }

            // ��ȡ���� ֧�ֶ�ά����
            $value = $this->getDataValue($data, $key);

            // �ֶ���֤
            if ($rule instanceof Closure) {
                $result = call_user_func_array($rule, [$value, $data]);
            } elseif ($rule instanceof ValidateRule) {
                //  ��֤����
                $result = $this->checkItem($key, $value, $rule->getRule(), $data, $rule->getTitle() ?: $title, $rule->getMsg());
            } else {
                $result = $this->checkItem($key, $value, $rule, $data, $title);
            }

            if (true !== $result) {
                // û�з���true ���ʾ��֤ʧ��
                if (!empty($this->batch)) {
                    // ������֤
                    if (is_array($result)) {
                        $this->error = array_merge($this->error, $result);
                    } else {
                        $this->error[$key] = $result;
                    }
                } elseif ($this->failException) {
                    throw new ValidateException($result);
                } else {
                    $this->error = $result;
                    return false;
                }
            }
        }

        if (!empty($this->error)) {
            if ($this->failException) {
                throw new ValidateException($this->error);
            }
            return false;
        }

        return true;
    }

    /**
     * ������֤������֤����
     * @access public
     * @param  mixed $value �ֶ�ֵ
     * @param  mixed $rules ��֤����
     * @return bool
     */
    public function checkRule($value, $rules): bool
    {
        if ($rules instanceof Closure) {
            return call_user_func_array($rules, [$value]);
        } elseif ($rules instanceof ValidateRule) {
            $rules = $rules->getRule();
        } elseif (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        foreach ($rules as $key => $rule) {
            if ($rule instanceof Closure) {
                $result = call_user_func_array($rule, [$value]);
            } else {
                // �ж���֤����
                list($type, $rule) = $this->getValidateType($key, $rule);

                $callback = $this->type[$type] ?? [$this, $type];

                $result = call_user_func_array($callback, [$value, $rule]);
            }

            if (true !== $result) {
                if ($this->failException) {
                    throw new ValidateException($result);
                }

                return $result;
            }
        }

        return true;
    }

    /**
     * ��֤�����ֶι���
     * @access protected
     * @param  string $field  �ֶ���
     * @param  mixed  $value  �ֶ�ֵ
     * @param  mixed  $rules  ��֤����
     * @param  array  $data  ����
     * @param  string $title  �ֶ�����
     * @param  array  $msg  ��ʾ��Ϣ
     * @return mixed
     */
    protected function checkItem(string $field, $value, $rules, $data, string $title = '', array $msg = [])
    {
        if (isset($this->remove[$field]) && true === $this->remove[$field] && empty($this->append[$field])) {
            // �ֶ��Ѿ��Ƴ� ������֤
            return true;
        }

        // ֧�ֶ������֤ require|in:a,b,c|... ���� ['require','in'=>'a,b,c',...]
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        if (isset($this->append[$field])) {
            // ׷�Ӷ������֤����
            $rules = array_unique(array_merge($rules, $this->append[$field]), SORT_REGULAR);
        }

        $i = 0;
        foreach ($rules as $key => $rule) {
            if ($rule instanceof Closure) {
                $result = call_user_func_array($rule, [$value, $data]);
                $info   = is_numeric($key) ? '' : $key;
            } else {
                // �ж���֤����
                list($type, $rule, $info) = $this->getValidateType($key, $rule);

                if (isset($this->append[$field]) && in_array($info, $this->append[$field])) {

                } elseif (isset($this->remove[$field]) && in_array($info, $this->remove[$field])) {
                    // �����Ѿ��Ƴ�
                    $i++;
                    continue;
                }

                if (isset($this->type[$type])) {
                    $result = call_user_func_array($this->type[$type], [$value, $rule, $data, $field, $title]);
                } elseif ('must' == $info || 0 === strpos($info, 'require') || (!is_null($value) && '' !== $value)) {
                    $result = call_user_func_array([$this, $type], [$value, $rule, $data, $field, $title]);
                } else {
                    $result = true;
                }
            }

            if (false === $result) {
                // ��֤ʧ�� ���ش�����Ϣ
                if (!empty($msg[$i])) {
                    $message = $msg[$i];
                    if (is_string($message) && strpos($message, '{%') === 0) {
                        $message = $this->lang->get(substr($message, 2, -1));
                    }
                } else {
                    $message = $this->getRuleMsg($field, $title, $info, $rule);
                }

                return $message;
            } elseif (true !== $result) {
                // �����Զ��������Ϣ
                if (is_string($result) && false !== strpos($result, ':')) {
                    $result = str_replace(':attribute', $title, $result);

                    if (strpos($result, ':rule') && is_scalar($rule)) {
                        $result = str_replace(':rule', (string) $rule, $result);
                    }
                }

                return $result;
            }
            $i++;
        }

        return $result;
    }

    /**
     * ��ȡ��ǰ��֤���ͼ�����
     * @access public
     * @param  mixed $key
     * @param  mixed $rule
     * @return array
     */
    protected function getValidateType($key, $rule): array
    {
        // �ж���֤����
        if (!is_numeric($key)) {
            if (isset($this->alias[$key])) {
                // �жϱ���
                $key = $this->alias[$key];
            }
            return [$key, $rule, $key];
        }

        if (strpos($rule, ':')) {
            list($type, $rule) = explode(':', $rule, 2);
            if (isset($this->alias[$type])) {
                // �жϱ���
                $type = $this->alias[$type];
            }
            $info = $type;
        } elseif (method_exists($this, $rule)) {
            $type = $rule;
            $info = $rule;
            $rule = '';
        } else {
            $type = 'is';
            $info = $rule;
        }

        return [$type, $rule, $info];
    }

    /**
     * ��֤�Ƿ��ĳ���ֶε�ֵһ��
     * @access public
     * @param  mixed  $value �ֶ�ֵ
     * @param  mixed  $rule  ��֤����
     * @param  array  $data  ����
     * @param  string $field �ֶ���
     * @return bool
     */
    public function confirm($value, $rule, array $data = [], string $field = ''): bool
    {
        if ('' == $rule) {
            if (strpos($field, '_confirm')) {
                $rule = strstr($field, '_confirm', true);
            } else {
                $rule = $field . '_confirm';
            }
        }

        return $this->getDataValue($data, $rule) === $value;
    }

    /**
     * ��֤�Ƿ��ĳ���ֶε�ֵ�Ƿ�ͬ
     * @access public
     * @param  mixed $value �ֶ�ֵ
     * @param  mixed $rule  ��֤����
     * @param  array $data  ����
     * @return bool
     */
    public function different($value, $rule, array $data = []): bool
    {
        return $this->getDataValue($data, $rule) != $value;
    }

    /**
     * ��֤�Ƿ���ڵ���ĳ��ֵ
     * @access public
     * @param  mixed $value  �ֶ�ֵ
     * @param  mixed $rule  ��֤����
     * @param  array $data  ����
     * @return bool
     */
    public function egt($value, $rule, array $data = []): bool
    {
        return $value >= $this->getDataValue($data, $rule);
    }

    /**
     * ��֤�Ƿ����ĳ��ֵ
     * @access public
     * @param  mixed $value  �ֶ�ֵ
     * @param  mixed $rule  ��֤����
     * @param  array $data  ����
     * @return bool
     */
    public function gt($value, $rule, array $data = []): bool
    {
        return $value > $this->getDataValue($data, $rule);
    }

    /**
     * ��֤�Ƿ�С�ڵ���ĳ��ֵ
     * @access public
     * @param  mixed $value  �ֶ�ֵ
     * @param  mixed $rule  ��֤����
     * @param  array $data  ����
     * @return bool
     */
    public function elt($value, $rule, array $data = []): bool
    {
        return $value <= $this->getDataValue($data, $rule);
    }

    /**
     * ��֤�Ƿ�С��ĳ��ֵ
     * @access public
     * @param  mixed $value  �ֶ�ֵ
     * @param  mixed $rule  ��֤����
     * @param  array $data  ����
     * @return bool
     */
    public function lt($value, $rule, array $data = []): bool
    {
        return $value < $this->getDataValue($data, $rule);
    }

    /**
     * ��֤�Ƿ����ĳ��ֵ
     * @access public
     * @param  mixed $value  �ֶ�ֵ
     * @param  mixed $rule  ��֤����
     * @return bool
     */
    public function eq($value, $rule): bool
    {
        return $value == $rule;
    }

    /**
     * ������֤
     * @access public
     * @param  mixed $value  �ֶ�ֵ
     * @param  mixed $rule  ��֤����
     * @return bool
     */
    public function must($value, $rule = null): bool
    {
        return !empty($value) || '0' == $value;
    }

    /**
     * ��֤�ֶ�ֵ�Ƿ�Ϊ��Ч��ʽ
     * @access public
     * @param  mixed  $value  �ֶ�ֵ
     * @param  string $rule  ��֤����
     * @param  array  $data  ����
     * @return bool
     */
    public function is($value, string $rule, array $data = []): bool
    {
        switch (App::parseName($rule, 1, false)) {
            case 'require':
                // ����
                $result = !empty($value) || '0' == $value;
                break;
            case 'accepted':
                // ����
                $result = in_array($value, ['1', 'on', 'yes']);
                break;
            case 'date':
                // �Ƿ���һ����Ч����
                $result = false !== strtotime($value);
                break;
            case 'activeUrl':
                // �Ƿ�Ϊ��Ч����ַ
                $result = checkdnsrr($value);
                break;
            case 'boolean':
            case 'bool':
                // �Ƿ�Ϊ����ֵ
                $result = in_array($value, [true, false, 0, 1, '0', '1'], true);
                break;
            case 'number':
                $result = ctype_digit((string) $value);
                break;
            case 'alphaNum':
                $result = ctype_alnum($value);
                break;
            case 'array':
                // �Ƿ�Ϊ����
                $result = is_array($value);
                break;
            case 'file':
                $result = $value instanceof File;
                break;
            case 'image':
                $result = $value instanceof File && in_array($this->getImageType($value->getRealPath()), [1, 2, 3, 6]);
                break;
            case 'token':
                $result = $this->token($value, '__token__', $data);
                break;
            default:
                if (isset($this->type[$rule])) {
                    // ע�����֤����
                    $result = call_user_func_array($this->type[$rule], [$value]);
                } elseif (function_exists('ctype_' . $rule)) {
                    // ctype��֤����
                    $ctypeFun = 'ctype_' . $rule;
                    $result   = $ctypeFun($value);
                } elseif (isset($this->filter[$rule])) {
                    // Filter_var��֤����
                    $result = $this->filter($value, $this->filter[$rule]);
                } else {
                    // ������֤
                    $result = $this->regex($value, $rule);
                }
        }

        return $result;
    }

    // �ж�ͼ������
    protected function getImageType($image)
    {
        if (function_exists('exif_imagetype')) {
            return exif_imagetype($image);
        }

        try {
            $info = getimagesize($image);
            return $info ? $info[2] : false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * ��֤������
     * @access public
     * @param  mixed     $value  �ֶ�ֵ
     * @param  mixed     $rule  ��֤����
     * @param  array     $data  ����
     * @return bool
     */
    public function token($value, string $rule, array $data): bool
    {
        $rule = !empty($rule) ? $rule : '__token__';
        return $this->request->checkToken($rule, $data);
    }

    /**
     * ��֤�Ƿ�Ϊ�ϸ����������IP ֧��A��MX��NS��SOA��PTR��CNAME��AAAA��A6�� SRV��NAPTR��TXT ���� ANY����
     * @access public
     * @param  mixed $value  �ֶ�ֵ
     * @param  mixed $rule  ��֤����
     * @return bool
     */
    public function activeUrl(string $value, string $rule = 'MX'): bool
    {
        if (!in_array($rule, ['A', 'MX', 'NS', 'SOA', 'PTR', 'CNAME', 'AAAA', 'A6', 'SRV', 'NAPTR', 'TXT', 'ANY'])) {
            $rule = 'MX';
        }

        return checkdnsrr($value, $rule);
    }

    /**
     * ��֤�Ƿ���ЧIP
     * @access public
     * @param  mixed $value  �ֶ�ֵ
     * @param  mixed $rule  ��֤���� ipv4 ipv6
     * @return bool
     */
    public function ip($value, string $rule = 'ipv4'): bool
    {
        if (!in_array($rule, ['ipv4', 'ipv6'])) {
            $rule = 'ipv4';
        }

        return $this->filter($value, [FILTER_VALIDATE_IP, 'ipv6' == $rule ? FILTER_FLAG_IPV6 : FILTER_FLAG_IPV4]);
    }

    /**
     * ��֤�ϴ��ļ���׺
     * @access public
     * @param  mixed $file  �ϴ��ļ�
     * @param  mixed $rule  ��֤����
     * @return bool
     */
    public function fileExt($file, $rule): bool
    {
        if (is_array($file)) {
            foreach ($file as $item) {
                if (!($item instanceof File) || !$item->checkExt($rule)) {
                    return false;
                }
            }
            return true;
        } elseif ($file instanceof File) {
            return $file->checkExt($rule);
        }

        return false;
    }

    /**
     * ��֤�ϴ��ļ�����
     * @access public
     * @param  mixed $file  �ϴ��ļ�
     * @param  mixed $rule  ��֤����
     * @return bool
     */
    public function fileMime($file, $rule): bool
    {
        if (is_array($file)) {
            foreach ($file as $item) {
                if (!($item instanceof File) || !$item->checkMime($rule)) {
                    return false;
                }
            }
            return true;
        } elseif ($file instanceof File) {
            return $file->checkMime($rule);
        }

        return false;
    }

    /**
     * ��֤�ϴ��ļ���С
     * @access public
     * @param  mixed $file  �ϴ��ļ�
     * @param  mixed $rule  ��֤����
     * @return bool
     */
    public function fileSize($file, $rule): bool
    {
        if (is_array($file)) {
            foreach ($file as $item) {
                if (!($item instanceof File) || !$item->checkSize($rule)) {
                    return false;
                }
            }
            return true;
        } elseif ($file instanceof File) {
            return $file->checkSize($rule);
        }

        return false;
    }

    /**
     * ��֤ͼƬ�Ŀ�߼�����
     * @access public
     * @param  mixed $file  �ϴ��ļ�
     * @param  mixed $rule  ��֤����
     * @return bool
     */
    public function image($file, $rule): bool
    {
        if (!($file instanceof File)) {
            return false;
        }

        if ($rule) {
            $rule = explode(',', $rule);

            list($width, $height, $type) = getimagesize($file->getRealPath());

            if (isset($rule[2])) {
                $imageType = strtolower($rule[2]);

                if ('jpeg' == $imageType) {
                    $imageType = 'jpg';
                }

                if (image_type_to_extension($type, false) != $imageType) {
                    return false;
                }
            }

            list($w, $h) = $rule;

            return $w == $width && $h == $height;
        }

        return in_array($this->getImageType($file->getRealPath()), [1, 2, 3, 6]);
    }

    /**
     * ��֤ʱ��������Ƿ����ָ����ʽ
     * @access public
     * @param  mixed $value  �ֶ�ֵ
     * @param  mixed $rule  ��֤����
     * @return bool
     */
    public function dateFormat($value, $rule): bool
    {
        $info = date_parse_from_format($rule, $value);
        return 0 == $info['warning_count'] && 0 == $info['error_count'];
    }

    /**
     * ��֤�Ƿ�Ψһ
     * @access public
     * @param  mixed  $value  �ֶ�ֵ
     * @param  mixed  $rule  ��֤���� ��ʽ�����ݱ�,�ֶ���,�ų�ID,������
     * @param  array  $data  ����
     * @param  string $field  ��֤�ֶ���
     * @return bool
     */
    public function unique($value, $rule, array $data = [], string $field = ''): bool
    {
        if (is_string($rule)) {
            $rule = explode(',', $rule);
        }

        if (false !== strpos($rule[0], '\\')) {
            // ָ��ģ����
            $db = new $rule[0];
        } else {
            $db = $this->db->name($rule[0]);
        }

        $key = $rule[1] ?? $field;
        $map = [];

        if (strpos($key, '^')) {
            // ֧�ֶ���ֶ���֤
            $fields = explode('^', $key);
            foreach ($fields as $key) {
                if (isset($data[$key])) {
                    $map[] = [$key, '=', $data[$key]];
                }
            }
        } elseif (isset($data[$field])) {
            $map[] = [$key, '=', $data[$field]];
        } else {
            $map = [];
        }

        $pk = !empty($rule[3]) ? $rule[3] : $db->getPk();

        if (is_string($pk)) {
            if (isset($rule[2])) {
                $map[] = [$pk, '<>', $rule[2]];
            } elseif (isset($data[$pk])) {
                $map[] = [$pk, '<>', $data[$pk]];
            }
        }

        if ($db->where($map)->field($pk)->find()) {
            return false;
        }

        return true;
    }

    /**
     * ʹ��filter_var��ʽ��֤
     * @access public
     * @param  mixed $value  �ֶ�ֵ
     * @param  mixed $rule  ��֤����
     * @return bool
     */
    public function filter($value, $rule): bool
    {
        if (is_string($rule) && strpos($rule, ',')) {
            list($rule, $param) = explode(',', $rule);
        } elseif (is_array($rule)) {
            $param = $rule[1] ?? null;
            $rule  = $rule[0];
        } else {
            $param = null;
        }

        return false !== filter_var($value, is_int($rule) ? $rule : filter_id($rule), $param);
    }

    /**
     * ��֤ĳ���ֶε���ĳ��ֵ��ʱ�����
     * @access public
     * @param  mixed $value  �ֶ�ֵ
     * @param  mixed $rule  ��֤����
     * @param  array $data  ����
     * @return bool
     */
    public function requireIf($value, $rule, array $data = []): bool
    {
        list($field, $val) = explode(',', $rule);

        if ($this->getDataValue($data, $field) == $val) {
            return !empty($value) || '0' == $value;
        }

        return true;
    }

    /**
     * ͨ���ص�������֤ĳ���ֶ��Ƿ����
     * @access public
     * @param  mixed $value  �ֶ�ֵ
     * @param  mixed $rule  ��֤����
     * @param  array $data  ����
     * @return bool
     */
    public function requireCallback($value, $rule, array $data = []): bool
    {
        $result = call_user_func_array([$this, $rule], [$value, $data]);

        if ($result) {
            return !empty($value) || '0' == $value;
        }

        return true;
    }

    /**
     * ��֤ĳ���ֶ���ֵ������±���
     * @access public
     * @param  mixed $value  �ֶ�ֵ
     * @param  mixed $rule  ��֤����
     * @param  array $data  ����
     * @return bool
     */
    public function requireWith($value, $rule, array $data = []): bool
    {
        $val = $this->getDataValue($data, $rule);

        if (!empty($val)) {
            return !empty($value) || '0' == $value;
        }

        return true;
    }

    /**
     * ��֤ĳ���ֶ�û��ֵ������±���
     * @access public
     * @param  mixed $value  �ֶ�ֵ
     * @param  mixed $rule  ��֤����
     * @param  array $data  ����
     * @return bool
     */
    public function requireWithout($value, $rule, array $data = []): bool
    {
        $val = $this->getDataValue($data, $rule);

        if (empty($val)) {
            return !empty($value) || '0' == $value;
        }

        return true;
    }

    /**
     * ��֤�Ƿ��ڷ�Χ��
     * @access public
     * @param  mixed $value  �ֶ�ֵ
     * @param  mixed $rule  ��֤����
     * @return bool
     */
    public function in($value, $rule): bool
    {
        return in_array($value, is_array($rule) ? $rule : explode(',', $rule));
    }

    /**
     * ��֤�Ƿ���ĳ����Χ
     * @access public
     * @param  mixed $value  �ֶ�ֵ
     * @param  mixed $rule  ��֤����
     * @return bool
     */
    public function notIn($value, $rule): bool
    {
        return !in_array($value, is_array($rule) ? $rule : explode(',', $rule));
    }

    /**
     * between��֤����
     * @access public
     * @param  mixed $value  �ֶ�ֵ
     * @param  mixed $rule  ��֤����
     * @return bool
     */
    public function between($value, $rule): bool
    {
        if (is_string($rule)) {
            $rule = explode(',', $rule);
        }
        list($min, $max) = $rule;

        return $value >= $min && $value <= $max;
    }

    /**
     * ʹ��notbetween��֤����
     * @access public
     * @param  mixed $value  �ֶ�ֵ
     * @param  mixed $rule  ��֤����
     * @return bool
     */
    public function notBetween($value, $rule): bool
    {
        if (is_string($rule)) {
            $rule = explode(',', $rule);
        }
        list($min, $max) = $rule;

        return $value < $min || $value > $max;
    }

    /**
     * ��֤���ݳ���
     * @access public
     * @param  mixed $value  �ֶ�ֵ
     * @param  mixed $rule  ��֤����
     * @return bool
     */
    public function length($value, $rule): bool
    {
        if (is_array($value)) {
            $length = count($value);
        } elseif ($value instanceof File) {
            $length = $value->getSize();
        } else {
            $length = mb_strlen((string) $value);
        }

        if (is_string($rule) && strpos($rule, ',')) {
            // ��������
            list($min, $max) = explode(',', $rule);
            return $length >= $min && $length <= $max;
        }

        // ָ������
        return $length == $rule;
    }

    /**
     * ��֤������󳤶�
     * @access public
     * @param  mixed $value  �ֶ�ֵ
     * @param  mixed $rule  ��֤����
     * @return bool
     */
    public function max($value, $rule): bool
    {
        if (is_array($value)) {
            $length = count($value);
        } elseif ($value instanceof File) {
            $length = $value->getSize();
        } else {
            $length = mb_strlen((string) $value);
        }

        return $length <= $rule;
    }

    /**
     * ��֤������С����
     * @access public
     * @param  mixed $value  �ֶ�ֵ
     * @param  mixed $rule  ��֤����
     * @return bool
     */
    public function min($value, $rule): bool
    {
        if (is_array($value)) {
            $length = count($value);
        } elseif ($value instanceof File) {
            $length = $value->getSize();
        } else {
            $length = mb_strlen((string) $value);
        }

        return $length >= $rule;
    }

    /**
     * ��֤����
     * @access public
     * @param  mixed $value  �ֶ�ֵ
     * @param  mixed $rule  ��֤����
     * @param  array $data  ����
     * @return bool
     */
    public function after($value, $rule, array $data = []): bool
    {
        return strtotime($value) >= strtotime($rule);
    }

    /**
     * ��֤����
     * @access public
     * @param  mixed $value  �ֶ�ֵ
     * @param  mixed $rule  ��֤����
     * @param  array $data  ����
     * @return bool
     */
    public function before($value, $rule, array $data = []): bool
    {
        return strtotime($value) <= strtotime($rule);
    }

    /**
     * ��֤����
     * @access public
     * @param  mixed $value  �ֶ�ֵ
     * @param  mixed $rule  ��֤����
     * @param  array $data  ����
     * @return bool
     */
    public function afterWith($value, $rule, array $data = []): bool
    {
        $rule = $this->getDataValue($data, $rule);
        return !is_null($rule) && strtotime($value) >= strtotime($rule);
    }

    /**
     * ��֤����
     * @access public
     * @param  mixed $value  �ֶ�ֵ
     * @param  mixed $rule  ��֤����
     * @param  array $data  ����
     * @return bool
     */
    public function beforeWith($value, $rule, array $data = []): bool
    {
        $rule = $this->getDataValue($data, $rule);
        return !is_null($rule) && strtotime($value) <= strtotime($rule);
    }

    /**
     * ��֤��Ч��
     * @access public
     * @param  mixed $value  �ֶ�ֵ
     * @param  mixed $rule  ��֤����
     * @return bool
     */
    public function expire($value, $rule): bool
    {
        if (is_string($rule)) {
            $rule = explode(',', $rule);
        }

        list($start, $end) = $rule;

        if (!is_numeric($start)) {
            $start = strtotime($start);
        }

        if (!is_numeric($end)) {
            $end = strtotime($end);
        }

        return time() >= $start && time() <= $end;
    }

    /**
     * ��֤IP���
     * @access public
     * @param  mixed $value  �ֶ�ֵ
     * @param  mixed $rule  ��֤����
     * @return bool
     */
    public function allowIp($value, $rule): bool
    {
        return in_array($value, is_array($rule) ? $rule : explode(',', $rule));
    }

    /**
     * ��֤IP����
     * @access public
     * @param  mixed $value  �ֶ�ֵ
     * @param  mixed $rule  ��֤����
     * @return bool
     */
    public function denyIp($value, $rule): bool
    {
        return !in_array($value, is_array($rule) ? $rule : explode(',', $rule));
    }

    /**
     * ʹ��������֤����
     * @access public
     * @param  mixed $value  �ֶ�ֵ
     * @param  mixed $rule  ��֤���� ����������Ԥ����������
     * @return bool
     */
    public function regex($value, $rule): bool
    {
        if (isset($this->regex[$rule])) {
            $rule = $this->regex[$rule];
        } elseif (isset($this->defaultRegex[$rule])) {
            $rule = $this->defaultRegex[$rule];
        }

        if (is_string($rule) && 0 !== strpos($rule, '/') && !preg_match('/\/[imsU]{0,4}$/', $rule)) {
            // ����������ʽ�����˲���/
            $rule = '/^' . $rule . '$/';
        }

        return is_scalar($value) && 1 === preg_match($rule, (string) $value);
    }

    // ��ȡ������Ϣ
    public function getError()
    {
        return $this->error;
    }

    /**
     * ��ȡ����ֵ
     * @access protected
     * @param  array  $data  ����
     * @param  string $key  ���ݱ�ʶ ֧�ֶ�ά
     * @return mixed
     */
    protected function getDataValue(array $data, $key)
    {
        if (is_numeric($key)) {
            $value = $key;
        } elseif (is_string($key) && strpos($key, '.')) {
            // ֧�ֶ�ά������֤
            foreach (explode('.', $key) as $key) {
                if (!isset($data[$key])) {
                    $value = null;
                    break;
                }
                $value = $data = $data[$key];
            }
        } else {
            $value = $data[$key] ?? null;
        }

        return $value;
    }

    /**
     * ��ȡ��֤����Ĵ�����ʾ��Ϣ
     * @access protected
     * @param  string $attribute  �ֶ�Ӣ����
     * @param  string $title  �ֶ�������
     * @param  string $type  ��֤��������
     * @param  mixed  $rule  ��֤��������
     * @return string
     */
    protected function getRuleMsg(string $attribute, string $title, string $type, $rule): string
    {
        if (isset($this->message[$attribute . '.' . $type])) {
            $msg = $this->message[$attribute . '.' . $type];
        } elseif (isset($this->message[$attribute][$type])) {
            $msg = $this->message[$attribute][$type];
        } elseif (isset($this->message[$attribute])) {
            $msg = $this->message[$attribute];
        } elseif (isset($this->typeMsg[$type])) {
            $msg = $this->typeMsg[$type];
        } elseif (0 === strpos($type, 'require')) {
            $msg = $this->typeMsg['require'];
        } else {
            $msg = $title . $this->lang->get('not conform to the rules');
        }

        if (!is_string($msg)) {
            return $msg;
        }

        if (0 === strpos($msg, '{%')) {
            $msg = $this->lang->get(substr($msg, 2, -1));
        } elseif ($this->lang->has($msg)) {
            $msg = $this->lang->get($msg);
        }

        if (is_scalar($rule) && false !== strpos($msg, ':')) {
            // �����滻
            if (is_string($rule) && strpos($rule, ',')) {
                $array = array_pad(explode(',', $rule), 3, '');
            } else {
                $array = array_pad([], 3, '');
            }

            $msg = str_replace(
                [':attribute', ':1', ':2', ':3'],
                [$title, $array[0], $array[1], $array[2]],
                $msg);

            if (strpos($msg, ':rule')) {
                $msg = str_replace(':rule', (string) $rule, $msg);
            }
        }

        return $msg;
    }

    /**
     * ��ȡ������֤�ĳ���
     * @access protected
     * @param  string $scene  ��֤����
     * @return void
     */
    protected function getScene(string $scene): void
    {
        $this->only = $this->append = $this->remove = [];

        if (method_exists($this, 'scene' . $scene)) {
            call_user_func([$this, 'scene' . $scene]);
        } elseif (isset($this->scene[$scene])) {
            // �����������֤���ó���
            $this->only = $this->scene[$scene];
        }
    }

    /**
     * ��̬���� ֱ�ӵ���is����������֤
     * @access public
     * @param  string $method  ������
     * @param  array $args  ���ò���
     * @return bool
     */
    public function __call($method, $args)
    {
        if ('is' == strtolower(substr($method, 0, 2))) {
            $method = substr($method, 2);
        }

        array_push($args, lcfirst($method));

        return call_user_func_array([$this, 'is'], $args);
    }
}
