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

namespace think\template;

use Exception;
use think\Template;

/**
 * ThinkPHP��ǩ��TagLib��������
 * @category   Think
 * @package  Think
 * @subpackage  Template
 * @author    liu21st <liu21st@gmail.com>
 */
class TagLib
{

    /**
     * ��ǩ�ⶨ��XML�ļ�
     * @var string
     * @access protected
     */
    protected $xml  = '';
    protected $tags = []; // ��ǩ����
    /**
     * ��ǩ������
     * @var string
     * @access protected
     */
    protected $tagLib = '';

    /**
     * ��ǩ���ǩ�б�
     * @var array
     * @access protected
     */
    protected $tagList = [];

    /**
     * ��ǩ���������
     * @var array
     * @access protected
     */
    protected $parse = [];

    /**
     * ��ǩ���Ƿ���Ч
     * @var bool
     * @access protected
     */
    protected $valid = false;

    /**
     * ��ǰģ�����
     * @var object
     * @access protected
     */
    protected $tpl;

    protected $comparison = [' nheq ' => ' !== ', ' heq ' => ' === ', ' neq ' => ' != ', ' eq ' => ' == ', ' egt ' => ' >= ', ' gt ' => ' > ', ' elt ' => ' <= ', ' lt ' => ' < '];

    /**
     * �ܹ�����
     * @access public
     * @param  Template $template ģ���������
     */
    public function __construct(Template $template)
    {
        $this->tpl = $template;
    }

    /**
     * ��ǩ����滻ҳ���еı�ǩ
     * @access public
     * @param  string $content ģ������
     * @param  string $lib ��ǩ����
     * @return void
     */
    public function parseTag(string &$content, string $lib = ''): void
    {
        $tags = [];
        $lib  = $lib ? strtolower($lib) . ':' : '';

        foreach ($this->tags as $name => $val) {
            $close                      = !isset($val['close']) || $val['close'] ? 1 : 0;
            $tags[$close][$lib . $name] = $name;
            if (isset($val['alias'])) {
                // ��������
                $array = (array) $val['alias'];
                foreach (explode(',', $array[0]) as $v) {
                    $tags[$close][$lib . $v] = $name;
                }
            }
        }

        // �պϱ�ǩ
        if (!empty($tags[1])) {
            $nodes = [];
            $regex = $this->getRegex(array_keys($tags[1]), 1);
            if (preg_match_all($regex, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
                $right = [];
                foreach ($matches as $match) {
                    if ('' == $match[1][0]) {
                        $name = strtolower($match[2][0]);
                        // �����û�պϵı�ǩͷ��ȡ�����һ��
                        if (!empty($right[$name])) {
                            // $match[0][1]Ϊ��ǩ��������ģ���е�λ��
                            $nodes[$match[0][1]] = [
                                'name'  => $name,
                                'begin' => array_pop($right[$name]), // ��ǩ��ʼ��
                                'end'   => $match[0], // ��ǩ������
                            ];
                        }
                    } else {
                        // ��ǩͷѹ��ջ
                        $right[strtolower($match[1][0])][] = $match[0];
                    }
                }
                unset($right, $matches);
                // ����ǩ��ģ���е�λ�ôӺ���ǰ����
                krsort($nodes);
            }

            $break = '<!--###break###--!>';
            if ($nodes) {
                $beginArray = [];
                // ��ǩ�滻 �Ӻ���ǰ
                foreach ($nodes as $pos => $node) {
                    // ��Ӧ�ı�ǩ��
                    $name  = $tags[1][$node['name']];
                    $alias = $lib . $name != $node['name'] ? ($lib ? strstr($node['name'], $lib) : $node['name']) : '';

                    // ������ǩ����
                    $attrs  = $this->parseAttr($node['begin'][0], $name, $alias);
                    $method = 'tag' . $name;

                    // ��ȡ��ǩ���ж�Ӧ�ı�ǩ���� replace[0]�����滻��ǩͷ��replace[1]�����滻��ǩβ
                    $replace = explode($break, $this->$method($attrs, $break));

                    if (count($replace) > 1) {
                        while ($beginArray) {
                            $begin = end($beginArray);
                            // �жϵ�ǰ��ǩβ��λ���Ƿ���ջ�����һ����ǩͷ�ĺ��棬����Ϊ�ӱ�ǩ
                            if ($node['end'][1] > $begin['pos']) {
                                break;
                            } else {
                                // ��Ϊ�ӱ�ǩʱ��ȡ��ջ�����һ����ǩͷ
                                $begin = array_pop($beginArray);
                                // �滻��ǩͷ��
                                $content = substr_replace($content, $begin['str'], $begin['pos'], $begin['len']);
                            }
                        }
                        // �滻��ǩβ��
                        $content = substr_replace($content, $replace[1], $node['end'][1], strlen($node['end'][0]));
                        // �ѱ�ǩͷѹ��ջ
                        $beginArray[] = ['pos' => $node['begin'][1], 'len' => strlen($node['begin'][0]), 'str' => $replace[0]];
                    }
                }

                while ($beginArray) {
                    $begin = array_pop($beginArray);
                    // �滻��ǩͷ��
                    $content = substr_replace($content, $begin['str'], $begin['pos'], $begin['len']);
                }
            }
        }
        // �Ապϱ�ǩ
        if (!empty($tags[0])) {
            $regex   = $this->getRegex(array_keys($tags[0]), 0);
            $content = preg_replace_callback($regex, function ($matches) use (&$tags, &$lib) {
                // ��Ӧ�ı�ǩ��
                $name  = $tags[0][strtolower($matches[1])];
                $alias = $lib . $name != $matches[1] ? ($lib ? strstr($matches[1], $lib) : $matches[1]) : '';
                // ������ǩ����
                $attrs  = $this->parseAttr($matches[0], $name, $alias);
                $method = 'tag' . $name;
                return $this->$method($attrs, '');
            }, $content);
        }
    }

    /**
     * ����ǩ��������
     * @access public
     * @param  array|string     $tags ��ǩ��
     * @param  boolean          $close �Ƿ�Ϊ�պϱ�ǩ
     * @return string
     */
    public function getRegex($tags, bool $close): string
    {
        $begin   = $this->tpl->getConfig('taglib_begin');
        $end     = $this->tpl->getConfig('taglib_end');
        $single  = strlen(ltrim($begin, '\\')) == 1 && strlen(ltrim($end, '\\')) == 1 ? true : false;
        $tagName = is_array($tags) ? implode('|', $tags) : $tags;

        if ($single) {
            if ($close) {
                // ����Ǳպϱ�ǩ
                $regex = $begin . '(?:(' . $tagName . ')\b(?>[^' . $end . ']*)|\/(' . $tagName . '))' . $end;
            } else {
                $regex = $begin . '(' . $tagName . ')\b(?>[^' . $end . ']*)' . $end;
            }
        } else {
            if ($close) {
                // ����Ǳպϱ�ǩ
                $regex = $begin . '(?:(' . $tagName . ')\b(?>(?:(?!' . $end . ').)*)|\/(' . $tagName . '))' . $end;
            } else {
                $regex = $begin . '(' . $tagName . ')\b(?>(?:(?!' . $end . ').)*)' . $end;
            }
        }

        return '/' . $regex . '/is';
    }

    /**
     * ������ǩ���� ����ʽ
     * @access public
     * @param  string $str ��ǩ�����ַ���
     * @param  string $name ��ǩ��
     * @param  string $alias ����
     * @return array
     */
    public function parseAttr(string $str, string $name, string $alias = ''): array
    {
        $regex  = '/\s+(?>(?P<name>[\w-]+)\s*)=(?>\s*)([\"\'])(?P<value>(?:(?!\\2).)*)\\2/is';
        $result = [];

        if (preg_match_all($regex, $str, $matches)) {
            foreach ($matches['name'] as $key => $val) {
                $result[$val] = $matches['value'][$key];
            }

            if (!isset($this->tags[$name])) {
                // ����Ƿ���ڱ�������
                foreach ($this->tags as $key => $val) {
                    if (isset($val['alias'])) {
                        $array = (array) $val['alias'];
                        if (in_array($name, explode(',', $array[0]))) {
                            $tag           = $val;
                            $type          = !empty($array[1]) ? $array[1] : 'type';
                            $result[$type] = $name;
                            break;
                        }
                    }
                }
            } else {
                $tag = $this->tags[$name];
                // �����˱�ǩ����
                if (!empty($alias) && isset($tag['alias'])) {
                    $type          = !empty($tag['alias'][1]) ? $tag['alias'][1] : 'type';
                    $result[$type] = $alias;
                }
            }

            if (!empty($tag['must'])) {
                $must = explode(',', $tag['must']);
                foreach ($must as $name) {
                    if (!isset($result[$name])) {
                        throw new Exception('tag attr must:' . $name);
                    }
                }
            }
        } else {
            // ����ֱ��ʹ�ñ��ʽ�ı�ǩ
            if (!empty($this->tags[$name]['expression'])) {
                static $_taglibs;
                if (!isset($_taglibs[$name])) {
                    $_taglibs[$name][0] = strlen($this->tpl->getConfig('taglib_begin_origin') . $name);
                    $_taglibs[$name][1] = strlen($this->tpl->getConfig('taglib_end_origin'));
                }
                $result['expression'] = substr($str, $_taglibs[$name][0], -$_taglibs[$name][1]);
                // ����Ապϱ�ǩβ��/
                $result['expression'] = rtrim($result['expression'], '/');
                $result['expression'] = trim($result['expression']);
            } elseif (empty($this->tags[$name]) || !empty($this->tags[$name]['attr'])) {
                throw new Exception('tag error:' . $name);
            }
        }

        return $result;
    }

    /**
     * �����������ʽ
     * @access public
     * @param  string $condition ���ʽ��ǩ����
     * @return string
     */
    public function parseCondition(string $condition): string
    {
        if (strpos($condition, ':')) {
            $condition = ' ' . substr(strstr($condition, ':'), 1);
        }

        $condition = str_ireplace(array_keys($this->comparison), array_values($this->comparison), $condition);
        $this->tpl->parseVar($condition);

        return $condition;
    }

    /**
     * �Զ�ʶ�𹹽�����
     * @access public
     * @param  string    $name       ��������
     * @return string
     */
    public function autoBuildVar(string &$name): string
    {
        $flag = substr($name, 0, 1);

        if (':' == $flag) {
            // ��:��ͷΪ�������ã�����ǰȥ��:
            $name = substr($name, 1);
        } elseif ('$' != $flag && preg_match('/[a-zA-Z_]/', $flag)) {
            // XXX: ����д�����ܻ���Ҫ�Ľ�
            // ��������Ҫ����
            if (defined($name)) {
                return $name;
            }

            // ����$��ͷ����Ҳ���ǳ������Զ�����$ǰ׺
            $name = '$' . $name;
        }

        $this->tpl->parseVar($name);
        $this->tpl->parseVarFunction($name, false);

        return $name;
    }

    /**
     * ��ȡ��ǩ�б�
     * @access public
     * @return array
     */
    public function getTags(): array
    {
        return $this->tags;
    }
}
