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

namespace think\validate;

/**
 * Class ValidateRule
 * @package think\validate
 * @method ValidateRule confirm(mixed $rule, string $msg = '') static ��֤�Ƿ��ĳ���ֶε�ֵһ��
 * @method ValidateRule different(mixed $rule, string $msg = '') static ��֤�Ƿ��ĳ���ֶε�ֵ�Ƿ�ͬ
 * @method ValidateRule egt(mixed $rule, string $msg = '') static ��֤�Ƿ���ڵ���ĳ��ֵ
 * @method ValidateRule gt(mixed $rule, string $msg = '') static ��֤�Ƿ����ĳ��ֵ
 * @method ValidateRule elt(mixed $rule, string $msg = '') static ��֤�Ƿ�С�ڵ���ĳ��ֵ
 * @method ValidateRule lt(mixed $rule, string $msg = '') static ��֤�Ƿ�С��ĳ��ֵ
 * @method ValidateRule eg(mixed $rule, string $msg = '') static ��֤�Ƿ����ĳ��ֵ
 * @method ValidateRule in(mixed $rule, string $msg = '') static ��֤�Ƿ��ڷ�Χ��
 * @method ValidateRule notIn(mixed $rule, string $msg = '') static ��֤�Ƿ���ĳ����Χ
 * @method ValidateRule between(mixed $rule, string $msg = '') static ��֤�Ƿ���ĳ������
 * @method ValidateRule notBetween(mixed $rule, string $msg = '') static ��֤�Ƿ���ĳ������
 * @method ValidateRule length(mixed $rule, string $msg = '') static ��֤���ݳ���
 * @method ValidateRule max(mixed $rule, string $msg = '') static ��֤������󳤶�
 * @method ValidateRule min(mixed $rule, string $msg = '') static ��֤������С����
 * @method ValidateRule after(mixed $rule, string $msg = '') static ��֤����
 * @method ValidateRule before(mixed $rule, string $msg = '') static ��֤����
 * @method ValidateRule expire(mixed $rule, string $msg = '') static ��֤��Ч��
 * @method ValidateRule allowIp(mixed $rule, string $msg = '') static ��֤IP���
 * @method ValidateRule denyIp(mixed $rule, string $msg = '') static ��֤IP����
 * @method ValidateRule regex(mixed $rule, string $msg = '') static ʹ��������֤����
 * @method ValidateRule token(mixed $rule='__token__', string $msg = '') static ��֤������
 * @method ValidateRule is(mixed $rule, string $msg = '') static ��֤�ֶ�ֵ�Ƿ�Ϊ��Ч��ʽ
 * @method ValidateRule isRequire(mixed $rule = null, string $msg = '') static ��֤�ֶα���
 * @method ValidateRule isNumber(mixed $rule = null, string $msg = '') static ��֤�ֶ�ֵ�Ƿ�Ϊ����
 * @method ValidateRule isArray(mixed $rule = null, string $msg = '') static ��֤�ֶ�ֵ�Ƿ�Ϊ����
 * @method ValidateRule isInteger(mixed $rule = null, string $msg = '') static ��֤�ֶ�ֵ�Ƿ�Ϊ����
 * @method ValidateRule isFloat(mixed $rule = null, string $msg = '') static ��֤�ֶ�ֵ�Ƿ�Ϊ������
 * @method ValidateRule isMobile(mixed $rule = null, string $msg = '') static ��֤�ֶ�ֵ�Ƿ�Ϊ�ֻ�
 * @method ValidateRule isIdCard(mixed $rule = null, string $msg = '') static ��֤�ֶ�ֵ�Ƿ�Ϊ���֤����
 * @method ValidateRule isChs(mixed $rule = null, string $msg = '') static ��֤�ֶ�ֵ�Ƿ�Ϊ����
 * @method ValidateRule isChsDash(mixed $rule = null, string $msg = '') static ��֤�ֶ�ֵ�Ƿ�Ϊ������ĸ���»���
 * @method ValidateRule isChsAlpha(mixed $rule = null, string $msg = '') static ��֤�ֶ�ֵ�Ƿ�Ϊ���ĺ���ĸ
 * @method ValidateRule isChsAlphaNum(mixed $rule = null, string $msg = '') static ��֤�ֶ�ֵ�Ƿ�Ϊ������ĸ������
 * @method ValidateRule isDate(mixed $rule = null, string $msg = '') static ��֤�ֶ�ֵ�Ƿ�Ϊ��Ч��ʽ
 * @method ValidateRule isBool(mixed $rule = null, string $msg = '') static ��֤�ֶ�ֵ�Ƿ�Ϊ����ֵ
 * @method ValidateRule isAlpha(mixed $rule = null, string $msg = '') static ��֤�ֶ�ֵ�Ƿ�Ϊ��ĸ
 * @method ValidateRule isAlphaDash(mixed $rule = null, string $msg = '') static ��֤�ֶ�ֵ�Ƿ�Ϊ��ĸ���»���
 * @method ValidateRule isAlphaNum(mixed $rule = null, string $msg = '') static ��֤�ֶ�ֵ�Ƿ�Ϊ��ĸ������
 * @method ValidateRule isAccepted(mixed $rule = null, string $msg = '') static ��֤�ֶ�ֵ�Ƿ�Ϊyes, on, ���� 1
 * @method ValidateRule isEmail(mixed $rule = null, string $msg = '') static ��֤�ֶ�ֵ�Ƿ�Ϊ��Ч�����ʽ
 * @method ValidateRule isUrl(mixed $rule = null, string $msg = '') static ��֤�ֶ�ֵ�Ƿ�Ϊ��ЧURL��ַ
 * @method ValidateRule activeUrl(mixed $rule, string $msg = '') static ��֤�Ƿ�Ϊ�ϸ����������IP
 * @method ValidateRule ip(mixed $rule, string $msg = '') static ��֤�Ƿ���ЧIP
 * @method ValidateRule fileExt(mixed $rule, string $msg = '') static ��֤�ļ���׺
 * @method ValidateRule fileMime(mixed $rule, string $msg = '') static ��֤�ļ�����
 * @method ValidateRule fileSize(mixed $rule, string $msg = '') static ��֤�ļ���С
 * @method ValidateRule image(mixed $rule, string $msg = '') static ��֤ͼ���ļ�
 * @method ValidateRule method(mixed $rule, string $msg = '') static ��֤��������
 * @method ValidateRule dateFormat(mixed $rule, string $msg = '') static ��֤ʱ��������Ƿ����ָ����ʽ
 * @method ValidateRule unique(mixed $rule, string $msg = '') static ��֤�Ƿ�Ψһ
 * @method ValidateRule behavior(mixed $rule, string $msg = '') static ʹ����Ϊ����֤
 * @method ValidateRule filter(mixed $rule, string $msg = '') static ʹ��filter_var��ʽ��֤
 * @method ValidateRule requireIf(mixed $rule, string $msg = '') static ��֤ĳ���ֶε���ĳ��ֵ��ʱ�����
 * @method ValidateRule requireCallback(mixed $rule, string $msg = '') static ͨ���ص�������֤ĳ���ֶ��Ƿ����
 * @method ValidateRule requireWith(mixed $rule, string $msg = '') static ��֤ĳ���ֶ���ֵ������±���
 * @method ValidateRule must(mixed $rule = null, string $msg = '') static ������֤
 */
class ValidateRule
{
    // ��֤�ֶε�����
    protected $title;

    // ��ǰ��֤����
    protected $rule = [];

    // ��֤��ʾ��Ϣ
    protected $message = [];

    /**
     * �����֤����
     * @access protected
     * @param  string    $name  ��֤����
     * @param  mixed     $rule  ��֤����
     * @param  string    $msg   ��ʾ��Ϣ
     * @return $this
     */
    protected function addItem(string $name, $rule = null, string $msg = '')
    {
        if ($rule || 0 === $rule) {
            $this->rule[$name] = $rule;
        } else {
            $this->rule[] = $name;
        }

        $this->message[] = $msg;

        return $this;
    }

    /**
     * ��ȡ��֤����
     * @access public
     * @return array
     */
    public function getRule(): array
    {
        return $this->rule;
    }

    /**
     * ��ȡ��֤�ֶ�����
     * @access public
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title ?: '';
    }

    /**
     * ��ȡ��֤��ʾ
     * @access public
     * @return array
     */
    public function getMsg(): array
    {
        return $this->message;
    }

    /**
     * ������֤�ֶ�����
     * @access public
     * @return $this
     */
    public function title(string $title)
    {
        $this->title = $title;

        return $this;
    }

    public function __call($method, $args)
    {
        if ('is' == strtolower(substr($method, 0, 2))) {
            $method = substr($method, 2);
        }

        array_unshift($args, lcfirst($method));

        return call_user_func_array([$this, 'addItem'], $args);
    }

    public static function __callStatic($method, $args)
    {
        $rule = new static();

        if ('is' == strtolower(substr($method, 0, 2))) {
            $method = substr($method, 2);
        }

        array_unshift($args, lcfirst($method));

        return call_user_func_array([$rule, 'addItem'], $args);
    }
}
