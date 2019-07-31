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

use think\template\exception\TemplateNotFoundException;

/**
 * ThinkPHP���������ģ������
 * ֧��XML��ǩ����ͨ��ǩ��ģ�����
 * ������ģ������ ֧�ֶ�̬����
 */
class Template
{
    /**
     * ģ�����
     * @var array
     */
    protected $data = [];

    /**
     * ģ�����ò���
     * @var array
     */
    protected $config = [
        'view_path'          => '', // ģ��·��
        'view_base'          => '',
        'view_suffix'        => 'html', // Ĭ��ģ���ļ���׺
        'view_depr'          => DIRECTORY_SEPARATOR,
        'cache_path'         => '',
        'cache_suffix'       => 'php', // Ĭ��ģ�建���׺
        'tpl_deny_func_list' => 'echo,exit', // ģ��������ú���
        'tpl_deny_php'       => false, // Ĭ��ģ�������Ƿ����PHPԭ������
        'tpl_begin'          => '{', // ģ��������ͨ��ǩ��ʼ���
        'tpl_end'            => '}', // ģ��������ͨ��ǩ�������
        'strip_space'        => false, // �Ƿ�ȥ��ģ���ļ������html�ո��뻻��
        'tpl_cache'          => true, // �Ƿ���ģ����뻺��,��Ϊfalse��ÿ�ζ������±���
        'compile_type'       => 'file', // ģ���������
        'cache_prefix'       => '', // ģ�建��ǰ׺��ʶ�����Զ�̬�ı�
        'cache_time'         => 0, // ģ�建����Ч�� 0 Ϊ���ã�(������Ϊֵ����λ:��)
        'layout_on'          => false, // ����ģ�忪��
        'layout_name'        => 'layout', // ����ģ������ļ�
        'layout_item'        => '{__CONTENT__}', // ����ģ��������滻��ʶ
        'taglib_begin'       => '{', // ��ǩ���ǩ��ʼ���
        'taglib_end'         => '}', // ��ǩ���ǩ�������
        'taglib_load'        => true, // �Ƿ�ʹ�����ñ�ǩ��֮���������ǩ�⣬Ĭ���Զ����
        'taglib_build_in'    => 'cx', // ���ñ�ǩ������(��ǩʹ�ò���ָ����ǩ������),�Զ��ŷָ� ע�����˳��
        'taglib_pre_load'    => '', // ��Ҫ������صı�ǩ��(��ָ����ǩ������)������Զ��ŷָ�
        'display_cache'      => false, // ģ����Ⱦ����
        'cache_id'           => '', // ģ�建��ID
        'tpl_replace_string' => [],
        'tpl_var_identify'   => 'array', // .�﷨����ʶ��array|object|'', Ϊ��ʱ�Զ�ʶ��
        'default_filter'     => 'htmlentities', // Ĭ�Ϲ��˷��� ������ͨ��ǩ���
    ];

    /**
     * ����������Ϣ
     * @var array
     */
    private $literal = [];

    /**
     * ģ�������Ϣ
     * @var array
     */
    private $includeFile = [];

    /**
     * ģ��洢����
     * @var object
     */
    protected $storage;

    /**
     * �ܹ�����
     * @access public
     * @param  array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);

        $this->config['taglib_begin_origin'] = $this->config['taglib_begin'];
        $this->config['taglib_end_origin']   = $this->config['taglib_end'];

        $this->config['taglib_begin'] = preg_quote($this->config['taglib_begin'], '/');
        $this->config['taglib_end']   = preg_quote($this->config['taglib_end'], '/');
        $this->config['tpl_begin']    = preg_quote($this->config['tpl_begin'], '/');
        $this->config['tpl_end']      = preg_quote($this->config['tpl_end'], '/');

        // ��ʼ��ģ�����洢��
        $type  = $this->config['compile_type'] ? $this->config['compile_type'] : 'File';
        $class = false !== strpos($type, '\\') ? $type : '\\think\\template\\driver\\' . ucwords($type);

        $this->storage = new $class();
    }

    /**
     * ģ�������ֵ
     * @access public
     * @param  mixed $name
     * @param  mixed $value
     * @return $this
     */
    public function assign(array $vars = [])
    {
        $this->data = array_merge($this->data, $vars);
        return $this;
    }

    /**
     * ģ�����������ֵ
     * @access public
     * @param  mixed $name
     * @param  mixed $value
     */
    public function __set($name, $value)
    {
        $this->config[$name] = $value;
    }

    /**
     * ģ����������
     * @access public
     * @param  array $config
     * @return $this
     */
    public function config(array $config)
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    /**
     * ��ȡģ������������
     * @access public
     * @param  string $name
     * @return mixed
     */
    public function getConfig(string $name)
    {
        return $this->config[$name] ?? null;
    }

    /**
     * ģ�������ȡ
     * @access public
     * @param  string $name ������
     * @return mixed
     */
    public function get(string $name = '')
    {
        if ('' == $name) {
            return $this->data;
        }

        $data = $this->data;

        foreach (explode('.', $name) as $key => $val) {
            if (isset($data[$val])) {
                $data = $data[$val];
            } else {
                $data = null;
                break;
            }
        }

        return $data;
    }

    /**
     * ��Ⱦģ���ļ�
     * @access public
     * @param  string    $template ģ���ļ�
     * @param  array     $vars ģ�����
     * @return void
     */
    public function fetch(string $template, array $vars = []): void
    {
        if ($vars) {
            $this->data = array_merge($this->data, $vars);
        }

        $template = $this->parseTemplateFile($template);

        if ($template) {
            $cacheFile = $this->config['cache_path'] . $this->config['cache_prefix'] . md5($this->config['layout_on'] . $this->config['layout_name'] . $template) . '.' . ltrim($this->config['cache_suffix'], '.');

            if (!$this->checkCache($cacheFile)) {
                // ������Ч ����ģ�����
                $content = file_get_contents($template);
                $this->compiler($content, $cacheFile);
            }

            // ҳ�滺��
            ob_start();
            ob_implicit_flush(0);

            // ��ȡ����洢
            $this->storage->read($cacheFile, $this->data);

            // ��ȡ����ջ���
            $content = ob_get_clean();

            echo $content;
        }
    }

    /**
     * ��Ⱦģ������
     * @access public
     * @param  string    $content ģ������
     * @param  array     $vars ģ�����
     * @return void
     */
    public function display(string $content, array $vars = []): void
    {
        if ($vars) {
            $this->data = array_merge($this->data, $vars);
        }

        $cacheFile = $this->config['cache_path'] . $this->config['cache_prefix'] . md5($content) . '.' . ltrim($this->config['cache_suffix'], '.');

        if (!$this->checkCache($cacheFile)) {
            // ������Ч ģ�����
            $this->compiler($content, $cacheFile);
        }

        // ��ȡ����洢
        $this->storage->read($cacheFile, $this->data);
    }

    /**
     * ���ò���
     * @access public
     * @param  mixed     $name ����ģ������ false ��رղ���
     * @param  string    $replace ����ģ�������滻��ʶ
     * @return $this
     */
    public function layout($name, string $replace = '')
    {
        if (false === $name) {
            // �رղ���
            $this->config['layout_on'] = false;
        } else {
            // ��������
            $this->config['layout_on'] = true;

            // ���Ʊ���Ϊ�ַ���
            if (is_string($name)) {
                $this->config['layout_name'] = $name;
            }

            if (!empty($replace)) {
                $this->config['layout_item'] = $replace;
            }
        }

        return $this;
    }

    /**
     * �����뻺���Ƿ���Ч
     * �����Ч����Ҫ���±���
     * @access private
     * @param  string $cacheFile �����ļ���
     * @return bool
     */
    private function checkCache(string $cacheFile): bool
    {
        if (!$this->config['tpl_cache'] || !is_file($cacheFile) || !$handle = @fopen($cacheFile, "r")) {
            return false;
        }

        // ��ȡ��һ��
        preg_match('/\/\*(.+?)\*\//', fgets($handle), $matches);

        if (!isset($matches[1])) {
            return false;
        }

        $includeFile = unserialize($matches[1]);

        if (!is_array($includeFile)) {
            return false;
        }

        // ���ģ���ļ��Ƿ��и���
        foreach ($includeFile as $path => $time) {
            if (is_file($path) && filemtime($path) > $time) {
                // ģ���ļ�����и����򻺴���Ҫ����
                return false;
            }
        }

        // ������洢�Ƿ���Ч
        return $this->storage->check($cacheFile, $this->config['cache_time']);
    }

    /**
     * ����ģ���ļ�����
     * @access private
     * @param  string    $content ģ������
     * @param  string    $cacheFile �����ļ���
     * @return void
     */
    private function compiler(string &$content, string $cacheFile): void
    {
        // �ж��Ƿ����ò���
        if ($this->config['layout_on']) {
            if (false !== strpos($content, '{__NOLAYOUT__}')) {
                // ���Ե������岻ʹ�ò���
                $content = str_replace('{__NOLAYOUT__}', '', $content);
            } else {
                // ��ȡ����ģ��
                $layoutFile = $this->parseTemplateFile($this->config['layout_name']);

                if ($layoutFile) {
                    // �滻���ֵ���������
                    $content = str_replace($this->config['layout_item'], $content, file_get_contents($layoutFile));
                }
            }
        } else {
            $content = str_replace('{__NOLAYOUT__}', '', $content);
        }

        // ģ�����
        $this->parse($content);

        if ($this->config['strip_space']) {
            /* ȥ��html�ո��뻻�� */
            $find    = ['~>\s+<~', '~>(\s+\n|\r)~'];
            $replace = ['><', '>'];
            $content = preg_replace($find, $replace, $content);
        }

        // �Ż����ɵ�php����
        $content = preg_replace('/\?>\s*<\?php\s(?!echo\b|\bend)/s', '', $content);

        // ģ��������
        $replace = $this->config['tpl_replace_string'];
        $content = str_replace(array_keys($replace), array_values($replace), $content);

        // ��Ӱ�ȫ���뼰ģ�����ü�¼
        $content = '<?php /*' . serialize($this->includeFile) . '*/ ?>' . "\n" . $content;
        // ����洢
        $this->storage->write($cacheFile, $content);

        $this->includeFile = [];
    }

    /**
     * ģ��������
     * ֧����ͨ��ǩ��TagLib���� ֧���Զ����ǩ��
     * @access public
     * @param  string $content Ҫ������ģ������
     * @return void
     */
    public function parse(string &$content): void
    {
        // ����Ϊ�ղ�����
        if (empty($content)) {
            return;
        }

        // �滻literal��ǩ����
        $this->parseLiteral($content);

        // �����̳�
        $this->parseExtend($content);

        // ��������
        $this->parseLayout($content);

        // ���include�﷨
        $this->parseInclude($content);

        // �滻�����ļ���literal��ǩ����
        $this->parseLiteral($content);

        // ���PHP�﷨
        $this->parsePhp($content);

        // ��ȡ��Ҫ����ı�ǩ���б�
        // ��ǩ��ֻ��Ҫ����һ�Σ�����������һ��
        // һ������ļ�����ǰ��
        // ��ʽ��<taglib name="html,mytag..." />
        // ��TAGLIB_LOAD����Ϊtrueʱ�Ż���м��
        if ($this->config['taglib_load']) {
            $tagLibs = $this->getIncludeTagLib($content);

            if (!empty($tagLibs)) {
                // �Ե����TagLib���н���
                foreach ($tagLibs as $tagLibName) {
                    $this->parseTagLib($tagLibName, $content);
                }
            }
        }

        // Ԥ�ȼ��صı�ǩ�� ������ÿ��ģ����ʹ��taglib��ǩ���� ������ʹ�ñ�ǩ��XMLǰ׺
        if ($this->config['taglib_pre_load']) {
            $tagLibs = explode(',', $this->config['taglib_pre_load']);

            foreach ($tagLibs as $tag) {
                $this->parseTagLib($tag, $content);
            }
        }

        // ���ñ�ǩ�� ����ʹ��taglib��ǩ����Ϳ���ʹ�� ���Ҳ���ʹ�ñ�ǩ��XMLǰ׺
        $tagLibs = explode(',', $this->config['taglib_build_in']);

        foreach ($tagLibs as $tag) {
            $this->parseTagLib($tag, $content, true);
        }

        // ������ͨģ���ǩ {$tagName}
        $this->parseTag($content);

        // ��ԭ���滻��Literal��ǩ
        $this->parseLiteral($content, true);
    }

    /**
     * ���PHP�﷨
     * @access private
     * @param  string $content Ҫ������ģ������
     * @return void
     * @throws \think\Exception
     */
    private function parsePhp(string &$content): void
    {
        // �̱�ǩ�����Ҫ��<?��ǩ��echo��ʽ��� �����޷��������xml��ʶ
        $content = preg_replace('/(<\?(?!php|=|$))/i', '<?php echo \'\\1\'; ?>' . "\n", $content);

        // PHP�﷨���
        if ($this->config['tpl_deny_php'] && false !== strpos($content, '<?php')) {
            throw new Exception('not allow php tag');
        }
    }

    /**
     * ����ģ���еĲ��ֱ�ǩ
     * @access private
     * @param  string $content Ҫ������ģ������
     * @return void
     */
    private function parseLayout(string &$content): void
    {
        // ��ȡģ���еĲ��ֱ�ǩ
        if (preg_match($this->getRegex('layout'), $content, $matches)) {
            // �滻Layout��ǩ
            $content = str_replace($matches[0], '', $content);
            // ����Layout��ǩ
            $array = $this->parseAttr($matches[0]);

            if (!$this->config['layout_on'] || $this->config['layout_name'] != $array['name']) {
                // ��ȡ����ģ��
                $layoutFile = $this->parseTemplateFile($array['name']);

                if ($layoutFile) {
                    $replace = isset($array['replace']) ? $array['replace'] : $this->config['layout_item'];
                    // �滻���ֵ���������
                    $content = str_replace($replace, $content, file_get_contents($layoutFile));
                }
            }
        } else {
            $content = str_replace('{__NOLAYOUT__}', '', $content);
        }
    }

    /**
     * ����ģ���е�include��ǩ
     * @access private
     * @param  string $content Ҫ������ģ������
     * @return void
     */
    private function parseInclude(string &$content): void
    {
        $regex = $this->getRegex('include');
        $func  = function ($template) use (&$func, &$regex, &$content) {
            if (preg_match_all($regex, $template, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $array = $this->parseAttr($match[0]);
                    $file  = $array['file'];
                    unset($array['file']);

                    // ����ģ���ļ�������ȡ����
                    $parseStr = $this->parseTemplateName($file);

                    foreach ($array as $k => $v) {
                        // ��$��ͷ�ַ���ת����ģ�����
                        if (0 === strpos($v, '$')) {
                            $v = $this->get(substr($v, 1));
                        }

                        $parseStr = str_replace('[' . $k . ']', $v, $parseStr);
                    }

                    $content = str_replace($match[0], $parseStr, $content);
                    // �ٴζ԰����ļ�����ģ�����
                    $func($parseStr);
                }
                unset($matches);
            }
        };

        // �滻ģ���е�include��ǩ
        $func($content);
    }

    /**
     * ����ģ���е�extend��ǩ
     * @access private
     * @param  string $content Ҫ������ģ������
     * @return void
     */
    private function parseExtend(string &$content): void
    {
        $regex  = $this->getRegex('extend');
        $array  = $blocks  = $baseBlocks  = [];
        $extend = '';

        $func = function ($template) use (&$func, &$regex, &$array, &$extend, &$blocks, &$baseBlocks) {
            if (preg_match($regex, $template, $matches)) {
                if (!isset($array[$matches['name']])) {
                    $array[$matches['name']] = 1;
                    // ��ȡ�̳�ģ��
                    $extend = $this->parseTemplateName($matches['name']);

                    // �ݹ���̳�
                    $func($extend);

                    // ȡ��block��ǩ����
                    $blocks = array_merge($blocks, $this->parseBlock($template));

                    return;
                }
            } else {
                // ȡ�ö���ģ��block��ǩ����
                $baseBlocks = $this->parseBlock($template, true);

                if (empty($extend)) {
                    // ��extend��ǩ����block��ǩ�����
                    $extend = $template;
                }
            }
        };

        $func($content);

        if (!empty($extend)) {
            if ($baseBlocks) {
                $children = [];
                foreach ($baseBlocks as $name => $val) {
                    $replace = $val['content'];

                    if (!empty($children[$name])) {
                        // �����������block��ǩ
                        foreach ($children[$name] as $key) {
                            $replace = str_replace($baseBlocks[$key]['begin'] . $baseBlocks[$key]['content'] . $baseBlocks[$key]['end'], $blocks[$key]['content'], $replace);
                        }
                    }

                    if (isset($blocks[$name])) {
                        // ����{__block__}��ʾ�����̳�ģ�����Ӧ��ǩ�ϲ��������Ǹ���
                        $replace = str_replace(['{__BLOCK__}', '{__block__}'], $replace, $blocks[$name]['content']);

                        if (!empty($val['parent'])) {
                            // �����������block��ǩ
                            $parent = $val['parent'];

                            if (isset($blocks[$parent])) {
                                $blocks[$parent]['content'] = str_replace($blocks[$name]['begin'] . $blocks[$name]['content'] . $blocks[$name]['end'], $replace, $blocks[$parent]['content']);
                            }

                            $blocks[$name]['content'] = $replace;
                            $children[$parent][]      = $name;

                            continue;
                        }
                    } elseif (!empty($val['parent'])) {
                        // ����ӱ�ǩû�б��̳�����ԭֵ
                        $children[$val['parent']][] = $name;
                        $blocks[$name]              = $val;
                    }

                    if (!$val['parent']) {
                        // �滻ģ���еĶ���block��ǩ
                        $extend = str_replace($val['begin'] . $val['content'] . $val['end'], $replace, $extend);
                    }
                }
            }

            $content = $extend;
            unset($blocks, $baseBlocks);
        }
    }

    /**
     * �滻ҳ���е�literal��ǩ
     * @access private
     * @param  string   $content ģ������
     * @param  boolean  $restore �Ƿ�Ϊ��ԭ
     * @return void
     */
    private function parseLiteral(string &$content, bool $restore = false): void
    {
        $regex = $this->getRegex($restore ? 'restoreliteral' : 'literal');

        if (preg_match_all($regex, $content, $matches, PREG_SET_ORDER)) {
            if (!$restore) {
                $count = count($this->literal);

                // �滻literal��ǩ
                foreach ($matches as $match) {
                    $this->literal[] = substr($match[0], strlen($match[1]), -strlen($match[2]));
                    $content         = str_replace($match[0], "<!--###literal{$count}###-->", $content);
                    $count++;
                }
            } else {
                // ��ԭliteral��ǩ
                foreach ($matches as $match) {
                    $content = str_replace($match[0], $this->literal[$match[1]], $content);
                }

                // ���literal��¼
                $this->literal = [];
            }

            unset($matches);
        }
    }

    /**
     * ��ȡģ���е�block��ǩ
     * @access private
     * @param  string   $content ģ������
     * @param  boolean  $sort �Ƿ�����
     * @return array
     */
    private function parseBlock(string &$content, bool $sort = false): array
    {
        $regex  = $this->getRegex('block');
        $result = [];

        if (preg_match_all($regex, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            $right = $keys = [];

            foreach ($matches as $match) {
                if (empty($match['name'][0])) {
                    if (count($right) > 0) {
                        $tag    = array_pop($right);
                        $start  = $tag['offset'] + strlen($tag['tag']);
                        $length = $match[0][1] - $start;

                        $result[$tag['name']] = [
                            'begin'   => $tag['tag'],
                            'content' => substr($content, $start, $length),
                            'end'     => $match[0][0],
                            'parent'  => count($right) ? end($right)['name'] : '',
                        ];

                        $keys[$tag['name']] = $match[0][1];
                    }
                } else {
                    // ��ǩͷѹ��ջ
                    $right[] = [
                        'name'   => $match[2][0],
                        'offset' => $match[0][1],
                        'tag'    => $match[0][0],
                    ];
                }
            }

            unset($right, $matches);

            if ($sort) {
                // ��block��ǩ��������ģ���е�λ������
                array_multisort($keys, $result);
            }
        }

        return $result;
    }

    /**
     * ����ģ��ҳ���а�����TagLib��
     * �������б�
     * @access private
     * @param  string $content ģ������
     * @return array|null
     */
    private function getIncludeTagLib(string &$content)
    {
        // �����Ƿ���TagLib��ǩ
        if (preg_match($this->getRegex('taglib'), $content, $matches)) {
            // �滻TagLib��ǩ
            $content = str_replace($matches[0], '', $content);

            return explode(',', $matches['name']);
        }
    }

    /**
     * TagLib�����
     * @access public
     * @param  string   $tagLib Ҫ�����ı�ǩ��
     * @param  string   $content Ҫ������ģ������
     * @param  boolean  $hide �Ƿ����ر�ǩ��ǰ׺
     * @return void
     */
    public function parseTagLib(string $tagLib, string &$content, bool $hide = false): void
    {
        if (false !== strpos($tagLib, '\\')) {
            // ֧��ָ����ǩ��������ռ�
            $className = $tagLib;
            $tagLib    = substr($tagLib, strrpos($tagLib, '\\') + 1);
        } else {
            $className = '\\think\\template\\taglib\\' . ucwords($tagLib);
        }

        $tLib = new $className($this);

        $tLib->parseTag($content, $hide ? '' : $tagLib);
    }

    /**
     * ������ǩ����
     * @access public
     * @param  string   $str �����ַ���
     * @param  string   $name ��Ϊ��ʱ����ָ����������
     * @return array
     */
    public function parseAttr(string $str, string $name = null): array
    {
        $regex = '/\s+(?>(?P<name>[\w-]+)\s*)=(?>\s*)([\"\'])(?P<value>(?:(?!\\2).)*)\\2/is';
        $array = [];

        if (preg_match_all($regex, $str, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $array[$match['name']] = $match['value'];
            }
            unset($matches);
        }

        if (!empty($name) && isset($array[$name])) {
            return $array[$name];
        }

        return $array;
    }

    /**
     * ģ���ǩ����
     * ��ʽ�� {TagName:args [|content] }
     * @access private
     * @param  string $content Ҫ������ģ������
     * @return void
     */
    private function parseTag(string &$content): void
    {
        $regex = $this->getRegex('tag');

        if (preg_match_all($regex, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $str  = stripslashes($match[1]);
                $flag = substr($str, 0, 1);

                switch ($flag) {
                    case '$':
                        // ����ģ����� ��ʽ {$varName}
                        // �Ƿ����?��
                        if (false !== $pos = strpos($str, '?')) {
                            $array = preg_split('/([!=]={1,2}|(?<!-)[><]={0,1})/', substr($str, 0, $pos), 2, PREG_SPLIT_DELIM_CAPTURE);
                            $name  = $array[0];

                            $this->parseVar($name);
                            //$this->parseVarFunction($name);

                            $str = trim(substr($str, $pos + 1));
                            $this->parseVar($str);
                            $first = substr($str, 0, 1);

                            if (strpos($name, ')')) {
                                // $nameΪ��������Զ�ʶ�𣬻��ߺ��к���
                                if (isset($array[1])) {
                                    $this->parseVar($array[2]);
                                    $name .= $array[1] . $array[2];
                                }

                                switch ($first) {
                                    case '?':
                                        $this->parseVarFunction($name);
                                        $str = '<?php echo (' . $name . ') ? ' . $name . ' : ' . substr($str, 1) . '; ?>';
                                        break;
                                    case '=':
                                        $str = '<?php if(' . $name . ') echo ' . substr($str, 1) . '; ?>';
                                        break;
                                    default:
                                        $str = '<?php echo ' . $name . '?' . $str . '; ?>';
                                }
                            } else {
                                if (isset($array[1])) {
                                    $express = true;
                                    $this->parseVar($array[2]);
                                    $express = $name . $array[1] . $array[2];
                                } else {
                                    $express = false;
                                }

                                if (in_array($first, ['?', '=', ':'])) {
                                    $str = trim(substr($str, 1));
                                    if ('$' == substr($str, 0, 1)) {
                                        $str = $this->parseVarFunction($str);
                                    }
                                }

                                // $nameΪ����
                                switch ($first) {
                                    case '?':
                                        // {$varname??'xxx'} $varname�ж��������$varname,�������xxx
                                        $str = '<?php echo ' . ($express ?: 'isset(' . $name . ')') . ' ? ' . $this->parseVarFunction($name) . ' : ' . $str . '; ?>';
                                        break;
                                    case '=':
                                        // {$varname?='xxx'} $varnameΪ��ʱ�����xxx
                                        $str = '<?php if(' . ($express ?: '!empty(' . $name . ')') . ') echo ' . $str . '; ?>';
                                        break;
                                    case ':':
                                        // {$varname?:'xxx'} $varnameΪ��ʱ���$varname,�������xxx
                                        $str = '<?php echo ' . ($express ?: '!empty(' . $name . ')') . ' ? ' . $this->parseVarFunction($name) . ' : ' . $str . '; ?>';
                                        break;
                                    default:
                                        if (strpos($str, ':')) {
                                            // {$varname ? 'a' : 'b'} $varnameΪ��ʱ���a,�������b
                                            $array = explode(':', $str, 2);

                                            $array[0] = '$' == substr(trim($array[0]), 0, 1) ? $this->parseVarFunction($array[0]) : $array[0];
                                            $array[1] = '$' == substr(trim($array[1]), 0, 1) ? $this->parseVarFunction($array[1]) : $array[1];

                                            $str = implode(' : ', $array);
                                        }
                                        $str = '<?php echo ' . ($express ?: '!empty(' . $name . ')') . ' ? ' . $str . '; ?>';
                                }
                            }
                        } else {
                            $this->parseVar($str);
                            $this->parseVarFunction($str);
                            $str = '<?php echo ' . $str . '; ?>';
                        }
                        break;
                    case ':':
                        // ���ĳ�������Ľ��
                        $str = substr($str, 1);
                        $this->parseVar($str);
                        $str = '<?php echo ' . $str . '; ?>';
                        break;
                    case '~':
                        // ִ��ĳ������
                        $str = substr($str, 1);
                        $this->parseVar($str);
                        $str = '<?php ' . $str . '; ?>';
                        break;
                    case '-':
                    case '+':
                        // �������
                        $this->parseVar($str);
                        $str = '<?php echo ' . $str . '; ?>';
                        break;
                    case '/':
                        // ע�ͱ�ǩ
                        $flag2 = substr($str, 1, 1);
                        if ('/' == $flag2 || ('*' == $flag2 && substr(rtrim($str), -2) == '*/')) {
                            $str = '';
                        }
                        break;
                    default:
                        // δʶ��ı�ǩֱ�ӷ���
                        $str = $this->config['tpl_begin'] . $str . $this->config['tpl_end'];
                        break;
                }

                $content = str_replace($match[0], $str, $content);
            }

            unset($matches);
        }
    }

    /**
     * ģ���������,֧��ʹ�ú���
     * ��ʽ�� {$varname|function1|function2=arg1,arg2}
     * @access public
     * @param  string $varStr ��������
     * @return void
     */
    public function parseVar(string &$varStr): void
    {
        $varStr = trim($varStr);

        if (preg_match_all('/\$[a-zA-Z_](?>\w*)(?:[:\.][0-9a-zA-Z_](?>\w*))+/', $varStr, $matches, PREG_OFFSET_CAPTURE)) {
            static $_varParseList = [];

            while ($matches[0]) {
                $match = array_pop($matches[0]);

                //����Ѿ��������ñ����ִ�����ֱ�ӷ��ر���ֵ
                if (isset($_varParseList[$match[0]])) {
                    $parseStr = $_varParseList[$match[0]];
                } else {
                    if (strpos($match[0], '.')) {
                        $vars  = explode('.', $match[0]);
                        $first = array_shift($vars);

                        if ('$Think' == $first) {
                            // ������Think.��ͷ������������Դ� ����ģ�帳ֵ�Ϳ������
                            $parseStr = $this->parseThinkVar($vars);
                        } elseif ('$Request' == $first) {
                            // ��ȡRequest����������
                            $method = array_shift($vars);
                            if (!empty($vars)) {
                                $params = implode('.', $vars);
                                if ('true' != $params) {
                                    $params = '\'' . $params . '\'';
                                }
                            } else {
                                $params = '';
                            }

                            $parseStr = 'app(\'request\')->' . $method . '(' . $params . ')';
                        } else {
                            switch ($this->config['tpl_var_identify']) {
                                case 'array': // ʶ��Ϊ����
                                    $parseStr = $first . '[\'' . implode('\'][\'', $vars) . '\']';
                                    break;
                                case 'obj': // ʶ��Ϊ����
                                    $parseStr = $first . '->' . implode('->', $vars);
                                    break;
                                default: // �Զ��ж���������
                                    $parseStr = '(is_array(' . $first . ')?' . $first . '[\'' . implode('\'][\'', $vars) . '\']:' . $first . '->' . implode('->', $vars) . ')';
                            }
                        }
                    } else {
                        $parseStr = str_replace(':', '->', $match[0]);
                    }

                    $_varParseList[$match[0]] = $parseStr;
                }

                $varStr = substr_replace($varStr, $parseStr, $match[1], strlen($match[0]));
            }
            unset($matches);
        }
    }

    /**
     * ��ģ����ʹ���˺����ı������н���
     * ��ʽ {$varname|function1|function2=arg1,arg2}
     * @access public
     * @param  string    $varStr     �����ַ���
     * @param  bool      $autoescape �Զ�ת��
     * @return string
     */
    public function parseVarFunction(string &$varStr, bool $autoescape = true): string
    {
        if (!$autoescape && false === strpos($varStr, '|')) {
            return $varStr;
        } elseif ($autoescape && !preg_match('/\|(\s)?raw(\||\s)?/i', $varStr)) {
            $varStr .= '|' . $this->config['default_filter'];
        }

        static $_varFunctionList = [];

        $_key = md5($varStr);

        //����Ѿ��������ñ����ִ�����ֱ�ӷ��ر���ֵ
        if (isset($_varFunctionList[$_key])) {
            $varStr = $_varFunctionList[$_key];
        } else {
            $varArray = explode('|', $varStr);

            // ȡ�ñ�������
            $name = trim(array_shift($varArray));

            // �Ա���ʹ�ú���
            $length = count($varArray);

            // ȡ��ģ���ֹʹ�ú����б�
            $template_deny_funs = explode(',', $this->config['tpl_deny_func_list']);

            for ($i = 0; $i < $length; $i++) {
                $args = explode('=', $varArray[$i], 2);

                // ģ�庯������
                $fun = trim($args[0]);
                if (in_array($fun, $template_deny_funs)) {
                    continue;
                }

                switch (strtolower($fun)) {
                    case 'raw':
                        break;
                    case 'date':
                        $name = 'date(' . $args[1] . ',!is_numeric(' . $name . ')? strtotime(' . $name . ') : ' . $name . ')';
                        break;
                    case 'first':
                        $name = 'current(' . $name . ')';
                        break;
                    case 'last':
                        $name = 'end(' . $name . ')';
                        break;
                    case 'upper':
                        $name = 'strtoupper(' . $name . ')';
                        break;
                    case 'lower':
                        $name = 'strtolower(' . $name . ')';
                        break;
                    case 'format':
                        $name = 'sprintf(' . $args[1] . ',' . $name . ')';
                        break;
                    case 'default': // ����ģ�庯��
                        if (false === strpos($name, '(')) {
                            $name = '(isset(' . $name . ') && (' . $name . ' !== \'\')?' . $name . ':' . $args[1] . ')';
                        } else {
                            $name = '(' . $name . ' ?: ' . $args[1] . ')';
                        }
                        break;
                    default: // ͨ��ģ�庯��
                        if (isset($args[1])) {
                            if (strstr($args[1], '###')) {
                                $args[1] = str_replace('###', $name, $args[1]);
                                $name    = "$fun($args[1])";
                            } else {
                                $name = "$fun($name,$args[1])";
                            }
                        } else {
                            if (!empty($args[0])) {
                                $name = "$fun($name)";
                            }
                        }
                }
            }

            $_varFunctionList[$_key] = $name;
            $varStr                  = $name;
        }
        return $varStr;
    }

    /**
     * ����ģ���������
     * ��ʽ �� $Think. ��ͷ�ı�����������ģ�����
     * @access public
     * @param  array $vars ��������
     * @return string
     */
    public function parseThinkVar(array $vars): string
    {
        $type  = strtoupper(trim(array_shift($vars)));
        $param = implode('.', $vars);

        if ($vars) {
            switch ($type) {
                case 'SERVER':
                    $parseStr = '$_SERVER[\'' . $param . '\']';
                    break;
                case 'GET':
                    $parseStr = '$_GET[\'' . $param . '\']';
                    break;
                case 'POST':
                    $parseStr = '$_POST[\'' . $param . '\']';
                    break;
                case 'COOKIE':
                    $parseStr = '$_COOKIE[\'' . $param . '\']';
                    break;
                case 'SESSION':
                    $parseStr = '$_SESSION[\'' . $param . '\']';
                    break;
                case 'ENV':
                    $parseStr = '$_ENV[\'' . $param . '\']';
                    break;
                case 'REQUEST':
                    $parseStr = '$_REQUEST[\'' . $param . '\']';
                    break;
                case 'CONST':
                    $parseStr = strtoupper($param);
                    break;
                default:
                    $parseStr = '\'\'';
                    break;
            }
        } else {
            switch ($type) {
                case 'NOW':
                    $parseStr = "date('Y-m-d g:i a',time())";
                    break;
                case 'LDELIM':
                    $parseStr = '\'' . ltrim($this->config['tpl_begin'], '\\') . '\'';
                    break;
                case 'RDELIM':
                    $parseStr = '\'' . ltrim($this->config['tpl_end'], '\\') . '\'';
                    break;
                default:
                    if (defined($type)) {
                        $parseStr = $type;
                    } else {
                        $parseStr = '';
                    }
            }
        }

        return $parseStr;
    }

    /**
     * �������ص�ģ���ļ�����ȡ���� ֧�ֶ��ģ���ļ���ȡ
     * @access private
     * @param  string $templateName ģ���ļ���
     * @return string
     */
    private function parseTemplateName(string $templateName): string
    {
        $array    = explode(',', $templateName);
        $parseStr = '';

        foreach ($array as $templateName) {
            if (empty($templateName)) {
                continue;
            }

            if (0 === strpos($templateName, '$')) {
                //֧�ּ��ر����ļ���
                $templateName = $this->get(substr($templateName, 1));
            }

            $template = $this->parseTemplateFile($templateName);

            if ($template) {
                // ��ȡģ���ļ�����
                $parseStr .= file_get_contents($template);
            }
        }

        return $parseStr;
    }

    /**
     * ����ģ���ļ���
     * @access private
     * @param  string $template �ļ���
     * @return string|false
     */
    private function parseTemplateFile(string $template): string
    {
        if ('' == pathinfo($template, PATHINFO_EXTENSION)) {
            if (strpos($template, '@')) {
                list($app, $template) = explode('@', $template);
            }

            if (0 !== strpos($template, '/')) {
                $template = str_replace(['/', ':'], $this->config['view_depr'], $template);
            } else {
                $template = str_replace(['/', ':'], $this->config['view_depr'], substr($template, 1));
            }

            if ($this->config['view_base']) {
                $app  = isset($app) ? $app : '';
                $path = $this->config['view_base'] . ($app ? $app . DIRECTORY_SEPARATOR : '');
            } else {
                $path = $this->config['view_path'];
            }

            $template = $path . $template . '.' . ltrim($this->config['view_suffix'], '.');
        }

        if (is_file($template)) {
            // ��¼ģ���ļ��ĸ���ʱ��
            $this->includeFile[$template] = filemtime($template);

            return $template;
        }

        throw new TemplateNotFoundException('template not exists:' . $template, $template);
    }

    /**
     * ����ǩ��������
     * @access private
     * @param  string $tagName ��ǩ��
     * @return string
     */
    private function getRegex(string $tagName): string
    {
        $regex = '';
        if ('tag' == $tagName) {
            $begin = $this->config['tpl_begin'];
            $end   = $this->config['tpl_end'];

            if (strlen(ltrim($begin, '\\')) == 1 && strlen(ltrim($end, '\\')) == 1) {
                $regex = $begin . '((?:[\$]{1,2}[a-wA-w_]|[\:\~][\$a-wA-w_]|[+]{2}[\$][a-wA-w_]|[-]{2}[\$][a-wA-w_]|\/[\*\/])(?>[^' . $end . ']*))' . $end;
            } else {
                $regex = $begin . '((?:[\$]{1,2}[a-wA-w_]|[\:\~][\$a-wA-w_]|[+]{2}[\$][a-wA-w_]|[-]{2}[\$][a-wA-w_]|\/[\*\/])(?>(?:(?!' . $end . ').)*))' . $end;
            }
        } else {
            $begin  = $this->config['taglib_begin'];
            $end    = $this->config['taglib_end'];
            $single = strlen(ltrim($begin, '\\')) == 1 && strlen(ltrim($end, '\\')) == 1 ? true : false;

            switch ($tagName) {
                case 'block':
                    if ($single) {
                        $regex = $begin . '(?:' . $tagName . '\b\s+(?>(?:(?!name=).)*)\bname=([\'\"])(?P<name>[\$\w\-\/\.]+)\\1(?>[^' . $end . ']*)|\/' . $tagName . ')' . $end;
                    } else {
                        $regex = $begin . '(?:' . $tagName . '\b\s+(?>(?:(?!name=).)*)\bname=([\'\"])(?P<name>[\$\w\-\/\.]+)\\1(?>(?:(?!' . $end . ').)*)|\/' . $tagName . ')' . $end;
                    }
                    break;
                case 'literal':
                    if ($single) {
                        $regex = '(' . $begin . $tagName . '\b(?>[^' . $end . ']*)' . $end . ')';
                        $regex .= '(?:(?>[^' . $begin . ']*)(?>(?!' . $begin . '(?>' . $tagName . '\b[^' . $end . ']*|\/' . $tagName . ')' . $end . ')' . $begin . '[^' . $begin . ']*)*)';
                        $regex .= '(' . $begin . '\/' . $tagName . $end . ')';
                    } else {
                        $regex = '(' . $begin . $tagName . '\b(?>(?:(?!' . $end . ').)*)' . $end . ')';
                        $regex .= '(?:(?>(?:(?!' . $begin . ').)*)(?>(?!' . $begin . '(?>' . $tagName . '\b(?>(?:(?!' . $end . ').)*)|\/' . $tagName . ')' . $end . ')' . $begin . '(?>(?:(?!' . $begin . ').)*))*)';
                        $regex .= '(' . $begin . '\/' . $tagName . $end . ')';
                    }
                    break;
                case 'restoreliteral':
                    $regex = '<!--###literal(\d+)###-->';
                    break;
                case 'include':
                    $name = 'file';
                case 'taglib':
                case 'layout':
                case 'extend':
                    if (empty($name)) {
                        $name = 'name';
                    }
                    if ($single) {
                        $regex = $begin . $tagName . '\b\s+(?>(?:(?!' . $name . '=).)*)\b' . $name . '=([\'\"])(?P<name>[\$\w\-\/\.\:@,\\\\]+)\\1(?>[^' . $end . ']*)' . $end;
                    } else {
                        $regex = $begin . $tagName . '\b\s+(?>(?:(?!' . $name . '=).)*)\b' . $name . '=([\'\"])(?P<name>[\$\w\-\/\.\:@,\\\\]+)\\1(?>(?:(?!' . $end . ').)*)' . $end;
                    }
                    break;
            }
        }

        return '/' . $regex . '/is';
    }

    public function __debugInfo()
    {
        $data = get_object_vars($this);
        unset($data['storage']);

        return $data;
    }
}
