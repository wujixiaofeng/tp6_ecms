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

namespace think\response;

use think\Collection;
use think\Model;
use think\Response;

/**
 * XML Response
 */
class Xml extends Response
{
    // �������
    protected $options = [
        // ���ڵ���
        'root_node' => 'think',
        // ���ڵ�����
        'root_attr' => '',
        //�����������ӽڵ���
        'item_node' => 'item',
        // ���������ӽڵ�keyת����������
        'item_key'  => 'id',
        // ���ݱ���
        'encoding'  => 'utf-8',
    ];

    protected $contentType = 'text/xml';

    /**
     * ��������
     * @access protected
     * @param  mixed $data Ҫ���������
     * @return mixed
     */
    protected function output($data): string
    {
        if (is_string($data)) {
            if (0 !== strpos($data, '<?xml')) {
                $encoding = $this->options['encoding'];
                $xml      = "<?xml version=\"1.0\" encoding=\"{$encoding}\"?>";
                $data     = $xml . $data;
            }
            return $data;
        }

        // XML����ת��
        return $this->xmlEncode($data, $this->options['root_node'], $this->options['item_node'], $this->options['root_attr'], $this->options['item_key'], $this->options['encoding']);
    }

    /**
     * XML����
     * @access protected
     * @param  mixed $data ����
     * @param  string $root ���ڵ���
     * @param  string $item �����������ӽڵ���
     * @param  mixed  $attr ���ڵ�����
     * @param  string $id   ���������ӽڵ�keyת����������
     * @param  string $encoding ���ݱ���
     * @return string
     */
    protected function xmlEncode($data, string $root, string $item, $attr, string $id, string $encoding): string
    {
        if (is_array($attr)) {
            $array = [];
            foreach ($attr as $key => $value) {
                $array[] = "{$key}=\"{$value}\"";
            }
            $attr = implode(' ', $array);
        }

        $attr = trim($attr);
        $attr = empty($attr) ? '' : " {$attr}";
        $xml  = "<?xml version=\"1.0\" encoding=\"{$encoding}\"?>";
        $xml .= "<{$root}{$attr}>";
        $xml .= $this->dataToXml($data, $item, $id);
        $xml .= "</{$root}>";

        return $xml;
    }

    /**
     * ����XML����
     * @access protected
     * @param  mixed  $data ����
     * @param  string $item ��������ʱ�Ľڵ�����
     * @param  string $id   ��������keyת��Ϊ��������
     * @return string
     */
    protected function dataToXml($data, string $item, string $id): string
    {
        $xml = $attr = '';

        if ($data instanceof Collection || $data instanceof Model) {
            $data = $data->toArray();
        }

        foreach ($data as $key => $val) {
            if (is_numeric($key)) {
                $id && $attr = " {$id}=\"{$key}\"";
                $key         = $item;
            }
            $xml .= "<{$key}{$attr}>";
            $xml .= (is_array($val) || is_object($val)) ? $this->dataToXml($val, $item, $id) : $val;
            $xml .= "</{$key}>";
        }

        return $xml;
    }
}
