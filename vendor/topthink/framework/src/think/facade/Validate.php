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
 * @see \think\Validate
 * @mixin \think\Validate
 * @method \think\Validate rule(mixed $name, mixed $rule = '') static ����ֶ���֤����
 * @method void extend(string $type, callable $callback = null, string $message='') static ע����չ��֤�����ͣ�����
 * @method void setTypeMsg(mixed $type, string $msg = null) static ������֤�����Ĭ����ʾ��Ϣ
 * @method \think\Validate message(mixed $name, string $message = '') static ������ʾ��Ϣ
 * @method \think\Validate scene(string $name) static ������֤����
 * @method bool hasScene(string $name) static �ж��Ƿ����ĳ����֤����
 * @method \think\Validate batch(bool $batch = true) static ����������֤
 * @method \think\Validate only(array $fields) static ָ����Ҫ��֤���ֶ��б�
 * @method \think\Validate remove(mixed $field, mixed $rule = true) static �Ƴ�ĳ���ֶε���֤����
 * @method \think\Validate append(mixed $field, mixed $rule = null) static ׷��ĳ���ֶε���֤����
 * @method bool confirm(mixed $value, mixed $rule, array $data = [], string $field = '') static ��֤�Ƿ��ĳ���ֶε�ֵһ��
 * @method bool different(mixed $value, mixed $rule, array $data = []) static ��֤�Ƿ��ĳ���ֶε�ֵ�Ƿ�ͬ
 * @method bool egt(mixed $value, mixed $rule, array $data = []) static ��֤�Ƿ���ڵ���ĳ��ֵ
 * @method bool gt(mixed $value, mixed $rule, array $data = []) static ��֤�Ƿ����ĳ��ֵ
 * @method bool elt(mixed $value, mixed $rule, array $data = []) static ��֤�Ƿ�С�ڵ���ĳ��ֵ
 * @method bool lt(mixed $value, mixed $rule, array $data = []) static ��֤�Ƿ�С��ĳ��ֵ
 * @method bool eq(mixed $value, mixed $rule) static ��֤�Ƿ����ĳ��ֵ
 * @method bool must(mixed $value, mixed $rule) static ������֤
 * @method bool is(mixed $value, mixed $rule, array $data = []) static ��֤�ֶ�ֵ�Ƿ�Ϊ��Ч��ʽ
 * @method bool ip(mixed $value, mixed $rule) static ��֤�Ƿ���ЧIP
 * @method bool requireIf(mixed $value, mixed $rule) static ��֤ĳ���ֶε���ĳ��ֵ��ʱ�����
 * @method bool requireCallback(mixed $value, mixed $rule,array $data) static ͨ���ص�������֤ĳ���ֶ��Ƿ����
 * @method bool requireWith(mixed $value, mixed $rule, array $data) static ��֤ĳ���ֶ���ֵ������±���
 * @method bool filter(mixed $value, mixed $rule) static ʹ��filter_var��ʽ��֤
 * @method bool in(mixed $value, mixed $rule) static ��֤�Ƿ��ڷ�Χ��
 * @method bool notIn(mixed $value, mixed $rule) static ��֤�Ƿ��ڷ�Χ��
 * @method bool between(mixed $value, mixed $rule) static between��֤����
 * @method bool notBetween(mixed $value, mixed $rule) static ʹ��notbetween��֤����
 * @method bool length(mixed $value, mixed $rule) static ��֤���ݳ���
 * @method bool max(mixed $value, mixed $rule) static ��֤������󳤶�
 * @method bool min(mixed $value, mixed $rule) static ��֤������С����
 * @method bool after(mixed $value, mixed $rule) static ��֤����
 * @method bool before(mixed $value, mixed $rule) static ��֤����
 * @method bool expire(mixed $value, mixed $rule) static ��֤��Ч��
 * @method bool allowIp(mixed $value, mixed $rule) static ��֤IP���
 * @method bool denyIp(mixed $value, mixed $rule) static ��֤IP����
 * @method bool regex(mixed $value, mixed $rule) static ʹ��������֤����
 * @method bool token(mixed $value, mixed $rule) static ��֤������
 * @method bool dateFormat(mixed $value, mixed $rule) static ��֤ʱ��������Ƿ����ָ����ʽ
 * @method bool unique(mixed $value, mixed $rule, array $data = [], string $field = '') static ��֤�Ƿ�Ψһ
 * @method bool check(array $data, mixed $rules = []) static �����Զ���֤
 * @method bool checkRule(mixed $data, mixed $rules = []) static �����ֶ���֤
 * @method bool isNumber(mixed $data) static ��֤�Ƿ�Ϊ�����֣�������������С���㣩
 * @method bool isAlpha(mixed $data) static ��֤�Ƿ�Ϊ����ĸ
 * @method bool isAlphaNum(mixed $data) static ��֤�Ƿ�Ϊ��ĸ������
 * @method bool isAlphaDash(mixed $data) static ��֤�Ƿ�Ϊ��ĸ�����֣��Լ��»���_�����ۺ�-
 * @method bool isChs(mixed $data) static ��֤�Ƿ�Ϊ����
 * @method bool isChsAlpha(mixed $data) static ��֤�Ƿ�Ϊ���ĺ���ĸ
 * @method bool isChsAlphaNum(mixed $data) static ��֤�Ƿ�Ϊ��ĸ������
 * @method bool isChsDash(mixed $data) static ��֤�Ƿ�Ϊ���ģ��Լ��»���_�����ۺ�-
 * @method bool isCntrl(mixed $data) static ��֤�Ƿ�Ϊ�����ַ������С��������ո�
 * @method bool isGraph(mixed $data) static ��֤�Ƿ�Ϊ�ɴ�ӡ�ַ����ո���⣩
 * @method bool isPrint(mixed $data) static ��֤�Ƿ�Ϊ�ɴ�ӡ�ַ��������ո�
 * @method bool isLower(mixed $data) static ��֤�Ƿ�ΪСд�ַ�
 * @method bool isUpper(mixed $data) static ��֤�Ƿ�Ϊ��д�ַ�
 * @method bool isSpace(mixed $data) static ��֤�Ƿ�Ϊ�հ��ַ���������������ֱ�Ʊ�������з����س��ͻ�ҳ�ַ���
 * @method bool isInteger(mixed $data) static ��֤�Ƿ�Ϊ����
 * @method bool isFloat(mixed $data) static ��֤�Ƿ�Ϊ������
 * @method bool isBool(mixed $data) static ��֤�Ƿ�Ϊ����ֵ
 * @method bool isEmail(mixed $data) static ��֤�Ƿ�Ϊ�����ַ
 * @method bool isArray(mixed $data) static ��֤�Ƿ�Ϊ����
 * @method bool isAccepted(mixed $data) static ��֤�Ƿ�Ϊyes, on, ���� 1
 * @method bool isDate(mixed $data) static ��֤�Ƿ�Ϊ���ڸ�ʽ
 * @method bool isXdigit(mixed $data) static ��֤�Ƿ�Ϊʮ�������ַ���
 * @method bool isActiveUrl(mixed $data) static ��֤�Ƿ�Ϊ��Ч����������IP
 * @method bool isUrl(mixed $data) static ��֤�Ƿ�Ϊ��Ч��URL��ַ
 * @method bool isMobile(mixed $data) static ��֤�Ƿ�Ϊ��Ч���ֻ�
 * @method bool isIp(mixed $data) static ��֤�Ƿ�Ϊ��Ч��IP
 * @method bool isIdCard(mixed $data) static ��֤�Ƿ�Ϊ��Ч�����֤����
 * @method bool isMacAddr(mixed $data) static ��֤�Ƿ�Ϊ��Ч��MAC��ַ
 * @method bool isZip(mixed $data) static ��֤�Ƿ�Ϊ��Ч���ʱ�
 * @method mixed getError() static ��ȡ������Ϣ
 */
class Validate extends Facade
{
    /**
     * ʼ�մ����µĶ���ʵ��
     * @var bool
     */
    protected static $alwaysNewInstance = true;

    /**
     * ��ȡ��ǰFacade��Ӧ�����������Ѿ��󶨵����������ʶ��
     * @access protected
     * @return string
     */
    protected static function getFacadeClass()
    {
        return 'validate';
    }
}
