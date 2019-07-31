<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace think\template\taglib;

use think\template\TagLib;

/**
 * CX��ǩ�������
 * @category   Think
 * @package  Think
 * @subpackage  Driver.Taglib
 * @author    liu21st <liu21st@gmail.com>
 */
class Cx extends Taglib
{

    // ��ǩ����
    protected $tags = [
        // ��ǩ���壺 attr �����б� close �Ƿ�պϣ�0 ����1 Ĭ��1�� alias ��ǩ���� level Ƕ�ײ��
        'php'        => ['attr' => ''],
        'volist'     => ['attr' => 'name,id,offset,length,key,mod', 'alias' => 'iterate'],
        'foreach'    => ['attr' => 'name,id,item,key,offset,length,mod', 'expression' => true],
        'if'         => ['attr' => 'condition', 'expression' => true],
        'elseif'     => ['attr' => 'condition', 'close' => 0, 'expression' => true],
        'else'       => ['attr' => '', 'close' => 0],
        'switch'     => ['attr' => 'name', 'expression' => true],
        'case'       => ['attr' => 'value,break', 'expression' => true],
        'default'    => ['attr' => '', 'close' => 0],
        'compare'    => ['attr' => 'name,value,type', 'alias' => ['eq,equal,notequal,neq,gt,lt,egt,elt,heq,nheq', 'type']],
        'range'      => ['attr' => 'name,value,type', 'alias' => ['in,notin,between,notbetween', 'type']],
        'empty'      => ['attr' => 'name'],
        'notempty'   => ['attr' => 'name'],
        'present'    => ['attr' => 'name'],
        'notpresent' => ['attr' => 'name'],
        'defined'    => ['attr' => 'name'],
        'notdefined' => ['attr' => 'name'],
        'load'       => ['attr' => 'file,href,type,value,basepath', 'close' => 0, 'alias' => ['import,css,js', 'type']],
        'assign'     => ['attr' => 'name,value', 'close' => 0],
        'define'     => ['attr' => 'name,value', 'close' => 0],
        'for'        => ['attr' => 'start,end,name,comparison,step'],
        'url'        => ['attr' => 'link,vars,suffix,domain', 'close' => 0, 'expression' => true],
        'function'   => ['attr' => 'name,vars,use,call'],
    ];

    /**
     * php��ǩ����
     * ��ʽ��
     * {php}echo $name{/php}
     * @access public
     * @param  array $tag ��ǩ����
     * @param  string $content ��ǩ����
     * @return string
     */
    public function tagPhp(array $tag, string $content): string
    {
        $parseStr = '<?php ' . $content . ' ?>';
        return $parseStr;
    }

    /**
     * volist��ǩ���� ѭ��������ݼ�
     * ��ʽ��
     * {volist name="userList" id="user" empty=""}
     * {user.username}
     * {user.email}
     * {/volist}
     * @access public
     * @param  array $tag ��ǩ����
     * @param  string $content ��ǩ����
     * @return string
     */
    public function tagVolist(array $tag, string $content): string
    {
        $name   = $tag['name'];
        $id     = $tag['id'];
        $empty  = isset($tag['empty']) ? $tag['empty'] : '';
        $key    = !empty($tag['key']) ? $tag['key'] : 'i';
        $mod    = isset($tag['mod']) ? $tag['mod'] : '2';
        $offset = !empty($tag['offset']) && is_numeric($tag['offset']) ? intval($tag['offset']) : 0;
        $length = !empty($tag['length']) && is_numeric($tag['length']) ? intval($tag['length']) : 'null';
        // ����ʹ�ú����趨���ݼ� <volist name=":fun('arg')" id="vo">{$vo.name}</volist>
        $parseStr = '<?php ';
        $flag     = substr($name, 0, 1);

        if (':' == $flag) {
            $name = $this->autoBuildVar($name);
            $parseStr .= '$_result=' . $name . ';';
            $name = '$_result';
        } else {
            $name = $this->autoBuildVar($name);
        }

        $parseStr .= 'if(is_array(' . $name . ') || ' . $name . ' instanceof \think\Collection || ' . $name . ' instanceof \think\Paginator): $' . $key . ' = 0;';

        // ������������鳤��
        if (0 != $offset || 'null' != $length) {
            $parseStr .= '$__LIST__ = is_array(' . $name . ') ? array_slice(' . $name . ',' . $offset . ',' . $length . ', true) : ' . $name . '->slice(' . $offset . ',' . $length . ', true); ';
        } else {
            $parseStr .= ' $__LIST__ = ' . $name . ';';
        }

        $parseStr .= 'if( count($__LIST__)==0 ) : echo "' . $empty . '" ;';
        $parseStr .= 'else: ';
        $parseStr .= 'foreach($__LIST__ as $key=>$' . $id . '): ';
        $parseStr .= '$mod = ($' . $key . ' % ' . $mod . ' );';
        $parseStr .= '++$' . $key . ';?>';
        $parseStr .= $content;
        $parseStr .= '<?php endforeach; endif; else: echo "' . $empty . '" ;endif; ?>';

        return $parseStr;
    }

    /**
     * foreach��ǩ���� ѭ��������ݼ�
     * ��ʽ��
     * {foreach name="userList" id="user" key="key" index="i" mod="2" offset="3" length="5" empty=""}
     * {user.username}
     * {/foreach}
     * @access public
     * @param  array $tag ��ǩ����
     * @param  string $content ��ǩ����
     * @return string
     */
    public function tagForeach(array $tag, string $content): string
    {
        // ֱ��ʹ�ñ��ʽ
        if (!empty($tag['expression'])) {
            $expression = ltrim(rtrim($tag['expression'], ')'), '(');
            $expression = $this->autoBuildVar($expression);
            $parseStr   = '<?php foreach(' . $expression . '): ?>';
            $parseStr .= $content;
            $parseStr .= '<?php endforeach; ?>';
            return $parseStr;
        }

        $name   = $tag['name'];
        $key    = !empty($tag['key']) ? $tag['key'] : 'key';
        $item   = !empty($tag['id']) ? $tag['id'] : $tag['item'];
        $empty  = isset($tag['empty']) ? $tag['empty'] : '';
        $offset = !empty($tag['offset']) && is_numeric($tag['offset']) ? intval($tag['offset']) : 0;
        $length = !empty($tag['length']) && is_numeric($tag['length']) ? intval($tag['length']) : 'null';

        $parseStr = '<?php ';

        // ֧���ú���������
        if (':' == substr($name, 0, 1)) {
            $var  = '$_' . uniqid();
            $name = $this->autoBuildVar($name);
            $parseStr .= $var . '=' . $name . '; ';
            $name = $var;
        } else {
            $name = $this->autoBuildVar($name);
        }

        $parseStr .= 'if(is_array(' . $name . ') || ' . $name . ' instanceof \think\Collection || ' . $name . ' instanceof \think\Paginator): ';

        // ������������鳤��
        if (0 != $offset || 'null' != $length) {
            if (!isset($var)) {
                $var = '$_' . uniqid();
            }
            $parseStr .= $var . ' = is_array(' . $name . ') ? array_slice(' . $name . ',' . $offset . ',' . $length . ', true) : ' . $name . '->slice(' . $offset . ',' . $length . ', true); ';
        } else {
            $var = &$name;
        }

        $parseStr .= 'if( count(' . $var . ')==0 ) : echo "' . $empty . '" ;';
        $parseStr .= 'else: ';

        // ������������
        if (isset($tag['index'])) {
            $index = $tag['index'];
            $parseStr .= '$' . $index . '=0; ';
        }

        $parseStr .= 'foreach(' . $var . ' as $' . $key . '=>$' . $item . '): ';

        // ������������
        if (isset($tag['index'])) {
            $index = $tag['index'];
            if (isset($tag['mod'])) {
                $mod = (int) $tag['mod'];
                $parseStr .= '$mod = ($' . $index . ' % ' . $mod . '); ';
            }
            $parseStr .= '++$' . $index . '; ';
        }

        $parseStr .= '?>';
        // ѭ�����е�����
        $parseStr .= $content;
        $parseStr .= '<?php endforeach; endif; else: echo "' . $empty . '" ;endif; ?>';

        return $parseStr;
    }

    /**
     * if��ǩ����
     * ��ʽ��
     * {if condition=" $a eq 1"}
     * {elseif condition="$a eq 2" /}
     * {else /}
     * {/if}
     * ���ʽ֧�� eq neq gt egt lt elt == > >= < <= or and || &&
     * @access public
     * @param  array $tag ��ǩ����
     * @param  string $content ��ǩ����
     * @return string
     */
    public function tagIf(array $tag, string $content): string
    {
        $condition = !empty($tag['expression']) ? $tag['expression'] : $tag['condition'];
        $condition = $this->parseCondition($condition);
        $parseStr  = '<?php if(' . $condition . '): ?>' . $content . '<?php endif; ?>';

        return $parseStr;
    }

    /**
     * elseif��ǩ����
     * ��ʽ����if��ǩ
     * @access public
     * @param  array $tag ��ǩ����
     * @param  string $content ��ǩ����
     * @return string
     */
    public function tagElseif(array $tag, string $content): string
    {
        $condition = !empty($tag['expression']) ? $tag['expression'] : $tag['condition'];
        $condition = $this->parseCondition($condition);
        $parseStr  = '<?php elseif(' . $condition . '): ?>';

        return $parseStr;
    }

    /**
     * else��ǩ����
     * ��ʽ����if��ǩ
     * @access public
     * @param  array $tag ��ǩ����
     * @return string
     */
    public function tagElse(array $tag): string
    {
        $parseStr = '<?php else: ?>';

        return $parseStr;
    }

    /**
     * switch��ǩ����
     * ��ʽ��
     * {switch name="a.name"}
     * {case value="1" break="false"}1{/case}
     * {case value="2" }2{/case}
     * {default /}other
     * {/switch}
     * @access public
     * @param  array $tag ��ǩ����
     * @param  string $content ��ǩ����
     * @return string
     */
    public function tagSwitch(array $tag, string $content): string
    {
        $name     = !empty($tag['expression']) ? $tag['expression'] : $tag['name'];
        $name     = $this->autoBuildVar($name);
        $parseStr = '<?php switch(' . $name . '): ?>' . $content . '<?php endswitch; ?>';

        return $parseStr;
    }

    /**
     * case��ǩ���� ��Ҫ���switch����Ч
     * @access public
     * @param  array $tag ��ǩ����
     * @param  string $content ��ǩ����
     * @return string
     */
    public function tagCase(array $tag, string $content): string
    {
        $value = isset($tag['expression']) ? $tag['expression'] : $tag['value'];
        $flag  = substr($value, 0, 1);

        if ('$' == $flag || ':' == $flag) {
            $value = $this->autoBuildVar($value);
            $value = 'case ' . $value . ':';
        } elseif (strpos($value, '|')) {
            $values = explode('|', $value);
            $value  = '';
            foreach ($values as $val) {
                $value .= 'case "' . addslashes($val) . '":';
            }
        } else {
            $value = 'case "' . $value . '":';
        }

        $parseStr = '<?php ' . $value . ' ?>' . $content;
        $isBreak  = isset($tag['break']) ? $tag['break'] : '';

        if ('' == $isBreak || $isBreak) {
            $parseStr .= '<?php break; ?>';
        }

        return $parseStr;
    }

    /**
     * default��ǩ���� ��Ҫ���switch����Ч
     * ʹ�ã� {default /}ddfdf
     * @access public
     * @param  array $tag ��ǩ����
     * @return string
     */
    public function tagDefault(array $tag): string
    {
        $parseStr = '<?php default: ?>';

        return $parseStr;
    }

    /**
     * compare��ǩ����
     * ����ֵ�ıȽ� ֧�� eq neq gt lt egt elt heq nheq Ĭ����eq
     * ��ʽ�� {compare name="" type="eq" value="" }content{/compare}
     * @access public
     * @param  array $tag ��ǩ����
     * @param  string $content ��ǩ����
     * @return string
     */
    public function tagCompare(array $tag, string $content): string
    {
        $name  = $tag['name'];
        $value = $tag['value'];
        $type  = isset($tag['type']) ? $tag['type'] : 'eq'; // �Ƚ�����
        $name  = $this->autoBuildVar($name);
        $flag  = substr($value, 0, 1);

        if ('$' == $flag || ':' == $flag) {
            $value = $this->autoBuildVar($value);
        } else {
            $value = '\'' . $value . '\'';
        }

        switch ($type) {
            case 'equal':
                $type = 'eq';
                break;
            case 'notequal':
                $type = 'neq';
                break;
        }
        $type     = $this->parseCondition(' ' . $type . ' ');
        $parseStr = '<?php if(' . $name . ' ' . $type . ' ' . $value . '): ?>' . $content . '<?php endif; ?>';

        return $parseStr;
    }

    /**
     * range��ǩ����
     * ���ĳ������������ĳ����Χ ��������� type= in ��ʾ�ڷ�Χ�� �����ʾ�ڷ�Χ��
     * ��ʽ�� {range name="var|function"  value="val" type='in|notin' }content{/range}
     * example: {range name="a"  value="1,2,3" type='in' }content{/range}
     * @access public
     * @param  array $tag ��ǩ����
     * @param  string $content ��ǩ����
     * @return string
     */
    public function tagRange(array $tag, string $content): string
    {
        $name  = $tag['name'];
        $value = $tag['value'];
        $type  = isset($tag['type']) ? $tag['type'] : 'in'; // �Ƚ�����

        $name = $this->autoBuildVar($name);
        $flag = substr($value, 0, 1);

        if ('$' == $flag || ':' == $flag) {
            $value = $this->autoBuildVar($value);
            $str   = 'is_array(' . $value . ')?' . $value . ':explode(\',\',' . $value . ')';
        } else {
            $value = '"' . $value . '"';
            $str   = 'explode(\',\',' . $value . ')';
        }

        if ('between' == $type) {
            $parseStr = '<?php $_RANGE_VAR_=' . $str . ';if(' . $name . '>= $_RANGE_VAR_[0] && ' . $name . '<= $_RANGE_VAR_[1]):?>' . $content . '<?php endif; ?>';
        } elseif ('notbetween' == $type) {
            $parseStr = '<?php $_RANGE_VAR_=' . $str . ';if(' . $name . '<$_RANGE_VAR_[0] || ' . $name . '>$_RANGE_VAR_[1]):?>' . $content . '<?php endif; ?>';
        } else {
            $fun      = ('in' == $type) ? 'in_array' : '!in_array';
            $parseStr = '<?php if(' . $fun . '((' . $name . '), ' . $str . ')): ?>' . $content . '<?php endif; ?>';
        }

        return $parseStr;
    }

    /**
     * present��ǩ����
     * ���ĳ�������Ѿ����� ���������
     * ��ʽ�� {present name="" }content{/present}
     * @access public
     * @param  array $tag ��ǩ����
     * @param  string $content ��ǩ����
     * @return string
     */
    public function tagPresent(array $tag, string $content): string
    {
        $name     = $tag['name'];
        $name     = $this->autoBuildVar($name);
        $parseStr = '<?php if(isset(' . $name . ')): ?>' . $content . '<?php endif; ?>';

        return $parseStr;
    }

    /**
     * notpresent��ǩ����
     * ���ĳ������û�����ã����������
     * ��ʽ�� {notpresent name="" }content{/notpresent}
     * @access public
     * @param  array $tag ��ǩ����
     * @param  string $content ��ǩ����
     * @return string
     */
    public function tagNotpresent(array $tag, string $content): string
    {
        $name     = $tag['name'];
        $name     = $this->autoBuildVar($name);
        $parseStr = '<?php if(!isset(' . $name . ')): ?>' . $content . '<?php endif; ?>';

        return $parseStr;
    }

    /**
     * empty��ǩ����
     * ���ĳ������Ϊempty ���������
     * ��ʽ�� {empty name="" }content{/empty}
     * @access public
     * @param  array $tag ��ǩ����
     * @param  string $content ��ǩ����
     * @return string
     */
    public function tagEmpty(array $tag, string $content): string
    {
        $name     = $tag['name'];
        $name     = $this->autoBuildVar($name);
        $parseStr = '<?php if(empty(' . $name . ') || ((' . $name . ' instanceof \think\Collection || ' . $name . ' instanceof \think\Paginator ) && ' . $name . '->isEmpty())): ?>' . $content . '<?php endif; ?>';

        return $parseStr;
    }

    /**
     * notempty��ǩ����
     * ���ĳ��������Ϊempty ���������
     * ��ʽ�� {notempty name="" }content{/notempty}
     * @access public
     * @param  array $tag ��ǩ����
     * @param  string $content ��ǩ����
     * @return string
     */
    public function tagNotempty(array $tag, string $content): string
    {
        $name     = $tag['name'];
        $name     = $this->autoBuildVar($name);
        $parseStr = '<?php if(!(empty(' . $name . ') || ((' . $name . ' instanceof \think\Collection || ' . $name . ' instanceof \think\Paginator ) && ' . $name . '->isEmpty()))): ?>' . $content . '<?php endif; ?>';

        return $parseStr;
    }

    /**
     * �ж��Ƿ��Ѿ������˸ó���
     * {defined name='TXT'}�Ѷ���{/defined}
     * @access public
     * @param  array $tag
     * @param  string $content
     * @return string
     */
    public function tagDefined(array $tag, string $content): string
    {
        $name     = $tag['name'];
        $parseStr = '<?php if(defined("' . $name . '")): ?>' . $content . '<?php endif; ?>';

        return $parseStr;
    }

    /**
     * �ж��Ƿ�û�ж����˸ó���
     * {notdefined name='TXT'}�Ѷ���{/notdefined}
     * @access public
     * @param  array $tag
     * @param  string $content
     * @return string
     */
    public function tagNotdefined(array $tag, string $content): string
    {
        $name     = $tag['name'];
        $parseStr = '<?php if(!defined("' . $name . '")): ?>' . $content . '<?php endif; ?>';

        return $parseStr;
    }

    /**
     * load ��ǩ���� {load file="/static/js/base.js" /}
     * ��ʽ��{load file="/static/css/base.css" /}
     * @access public
     * @param  array $tag ��ǩ����
     * @param  string $content ��ǩ����
     * @return string
     */
    public function tagLoad(array $tag, string $content): string
    {
        $file = isset($tag['file']) ? $tag['file'] : $tag['href'];
        $type = isset($tag['type']) ? strtolower($tag['type']) : '';

        $parseStr = '';
        $endStr   = '';

        // �ж��Ƿ���ڼ������� ����ʹ�ú����ж�(Ĭ��Ϊisset)
        if (isset($tag['value'])) {
            $name = $tag['value'];
            $name = $this->autoBuildVar($name);
            $name = 'isset(' . $name . ')';
            $parseStr .= '<?php if(' . $name . '): ?>';
            $endStr = '<?php endif; ?>';
        }

        // �ļ���ʽ����
        $array = explode(',', $file);

        foreach ($array as $val) {
            $type = strtolower(substr(strrchr($val, '.'), 1));
            switch ($type) {
                case 'js':
                    $parseStr .= '<script type="text/javascript" src="' . $val . '"></script>';
                    break;
                case 'css':
                    $parseStr .= '<link rel="stylesheet" type="text/css" href="' . $val . '" />';
                    break;
                case 'php':
                    $parseStr .= '<?php include "' . $val . '"; ?>';
                    break;
            }
        }

        return $parseStr . $endStr;
    }

    /**
     * assign��ǩ����
     * ��ģ���и�ĳ��������ֵ ֧�ֱ�����ֵ
     * ��ʽ�� {assign name="" value="" /}
     * @access public
     * @param  array $tag ��ǩ����
     * @param  string $content ��ǩ����
     * @return string
     */
    public function tagAssign(array $tag, string $content): string
    {
        $name = $this->autoBuildVar($tag['name']);
        $flag = substr($tag['value'], 0, 1);

        if ('$' == $flag || ':' == $flag) {
            $value = $this->autoBuildVar($tag['value']);
        } else {
            $value = '\'' . $tag['value'] . '\'';
        }

        $parseStr = '<?php ' . $name . ' = ' . $value . '; ?>';

        return $parseStr;
    }

    /**
     * define��ǩ����
     * ��ģ���ж��峣�� ֧�ֱ�����ֵ
     * ��ʽ�� {define name="" value="" /}
     * @access public
     * @param  array $tag ��ǩ����
     * @param  string $content ��ǩ����
     * @return string
     */
    public function tagDefine(array $tag, string $content): string
    {
        $name = '\'' . $tag['name'] . '\'';
        $flag = substr($tag['value'], 0, 1);

        if ('$' == $flag || ':' == $flag) {
            $value = $this->autoBuildVar($tag['value']);
        } else {
            $value = '\'' . $tag['value'] . '\'';
        }

        $parseStr = '<?php define(' . $name . ', ' . $value . '); ?>';

        return $parseStr;
    }

    /**
     * for��ǩ����
     * ��ʽ��
     * {for start="" end="" comparison="" step="" name=""}
     * content
     * {/for}
     * @access public
     * @param  array $tag ��ǩ����
     * @param  string $content ��ǩ����
     * @return string
     */
    public function tagFor(array $tag, string $content): string
    {
        //����Ĭ��ֵ
        $start      = 0;
        $end        = 0;
        $step       = 1;
        $comparison = 'lt';
        $name       = 'i';
        $rand       = rand(); //������������ֹǶ�ױ�����ͻ

        //��ȡ����
        foreach ($tag as $key => $value) {
            $value = trim($value);
            $flag  = substr($value, 0, 1);
            if ('$' == $flag || ':' == $flag) {
                $value = $this->autoBuildVar($value);
            }

            switch ($key) {
                case 'start':
                    $start = $value;
                    break;
                case 'end':
                    $end = $value;
                    break;
                case 'step':
                    $step = $value;
                    break;
                case 'comparison':
                    $comparison = $value;
                    break;
                case 'name':
                    $name = $value;
                    break;
            }
        }

        $parseStr = '<?php $__FOR_START_' . $rand . '__=' . $start . ';$__FOR_END_' . $rand . '__=' . $end . ';';
        $parseStr .= 'for($' . $name . '=$__FOR_START_' . $rand . '__;' . $this->parseCondition('$' . $name . ' ' . $comparison . ' $__FOR_END_' . $rand . '__') . ';$' . $name . '+=' . $step . '){ ?>';
        $parseStr .= $content;
        $parseStr .= '<?php } ?>';

        return $parseStr;
    }

    /**
     * url������tag��ǩ
     * ��ʽ��{url link="ģ��/������/����" vars="����" suffix="true����false �Ƿ���к�׺" domain="true����false �Ƿ�Я������" /}
     * @access public
     * @param  array $tag ��ǩ����
     * @param  string $content ��ǩ����
     * @return string
     */
    public function tagUrl(array $tag, string $content): string
    {
        $url    = isset($tag['link']) ? $tag['link'] : '';
        $vars   = isset($tag['vars']) ? $tag['vars'] : '';
        $suffix = isset($tag['suffix']) ? $tag['suffix'] : 'true';
        $domain = isset($tag['domain']) ? $tag['domain'] : 'false';

        return '<?php echo url("' . $url . '","' . $vars . '",' . $suffix . ',' . $domain . ');?>';
    }

    /**
     * function��ǩ���� ������������ʵ�ֵݹ�
     * ʹ�ã�
     * {function name="func" vars="$data" call="$list" use="&$a,&$b"}
     *      {if is_array($data)}
     *          {foreach $data as $val}
     *              {~func($val) /}
     *          {/foreach}
     *      {else /}
     *          {$data}
     *      {/if}
     * {/function}
     * @access public
     * @param  array $tag ��ǩ����
     * @param  string $content ��ǩ����
     * @return string
     */
    public function tagFunction(array $tag, string $content): string
    {
        $name = !empty($tag['name']) ? $tag['name'] : 'func';
        $vars = !empty($tag['vars']) ? $tag['vars'] : '';
        $call = !empty($tag['call']) ? $tag['call'] : '';
        $use  = ['&$' . $name];

        if (!empty($tag['use'])) {
            foreach (explode(',', $tag['use']) as $val) {
                $use[] = '&' . ltrim(trim($val), '&');
            }
        }

        $parseStr = '<?php $' . $name . '=function(' . $vars . ') use(' . implode(',', $use) . ') {';
        $parseStr .= ' ?>' . $content . '<?php }; ';
        $parseStr .= $call ? '$' . $name . '(' . $call . '); ?>' : '?>';

        return $parseStr;
    }
}
