<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2015 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace think\console;

class Table
{
    const ALIGN_LEFT   = 1;
    const ALIGN_RIGHT  = 0;
    const ALIGN_CENTER = 2;

    /**
     * ͷ��Ϣ����
     * @var array
     */
    protected $header = [];

    /**
     * ͷ�����뷽ʽ Ĭ��1 ALGIN_LEFT 0 ALIGN_RIGHT 2 ALIGN_CENTER
     * @var int
     */
    protected $headerAlign = 1;

    /**
     * ������ݣ���ά���飩
     * @var array
     */
    protected $rows = [];

    /**
     * ��Ԫ����뷽ʽ Ĭ��1 ALGIN_LEFT 0 ALIGN_RIGHT 2 ALIGN_CENTER
     * @var int
     */
    protected $cellAlign = 1;

    /**
     * ��Ԫ������Ϣ
     * @var array
     */
    protected $colWidth = [];

    /**
     * ��������ʽ
     * @var string
     */
    protected $style = 'default';

    /**
     * �����ʽ����
     * @var array
     */
    protected $format = [
        'compact'    => [],
        'default'    => [
            'top'          => ['+', '-', '+', '+'],
            'cell'         => ['|', ' ', '|', '|'],
            'middle'       => ['+', '-', '+', '+'],
            'bottom'       => ['+', '-', '+', '+'],
            'cross-top'    => ['+', '-', '-', '+'],
            'cross-bottom' => ['+', '-', '-', '+'],
        ],
        'markdown'   => [
            'top'          => [' ', ' ', ' ', ' '],
            'cell'         => ['|', ' ', '|', '|'],
            'middle'       => ['|', '-', '|', '|'],
            'bottom'       => [' ', ' ', ' ', ' '],
            'cross-top'    => ['|', ' ', ' ', '|'],
            'cross-bottom' => ['|', ' ', ' ', '|'],
        ],
        'borderless' => [
            'top'          => ['=', '=', ' ', '='],
            'cell'         => [' ', ' ', ' ', ' '],
            'middle'       => ['=', '=', ' ', '='],
            'bottom'       => ['=', '=', ' ', '='],
            'cross-top'    => ['=', '=', ' ', '='],
            'cross-bottom' => ['=', '=', ' ', '='],
        ],
        'box'        => [
            'top'          => ['��', '��', '��', '��'],
            'cell'         => ['��', ' ', '��', '��'],
            'middle'       => ['��', '��', '��', '��'],
            'bottom'       => ['��', '��', '��', '��'],
            'cross-top'    => ['��', '��', '��', '��'],
            'cross-bottom' => ['��', '��', '��', '��'],
        ],
        'box-double' => [
            'top'          => ['�X', '�T', '�h', '�['],
            'cell'         => ['�U', ' ', '��', '�U'],
            'middle'       => ['�d', '��', '�n', '�g'],
            'bottom'       => ['�^', '�T', '�k', '�a'],
            'cross-top'    => ['�d', '�T', '�k', '�g'],
            'cross-bottom' => ['�d', '�T', '�h', '�g'],
        ],
    ];

    /**
     * ���ñ��ͷ��Ϣ �Լ����뷽ʽ
     * @access public
     * @param array $header     Ҫ�����Header��Ϣ
     * @param int   $align      ���뷽ʽ Ĭ��1 ALGIN_LEFT 0 ALIGN_RIGHT 2 ALIGN_CENTER
     * @return void
     */
    public function setHeader(array $header, int $align = 1): void
    {
        $this->header      = $header;
        $this->headerAlign = $align;

        $this->checkColWidth($header);
    }

    /**
     * �������������� �����뷽ʽ
     * @access public
     * @param array $rows       Ҫ����ı�����ݣ���ά���飩
     * @param int   $align      ���뷽ʽ Ĭ��1 ALGIN_LEFT 0 ALIGN_RIGHT 2 ALIGN_CENTER
     * @return void
     */
    public function setRows(array $rows, int $align = 1): void
    {
        $this->rows      = $rows;
        $this->cellAlign = $align;

        foreach ($rows as $row) {
            $this->checkColWidth($row);
        }
    }

    /**
     * ��������ݵ���ʾ���
     * @access public
     * @param  mixed $row       ������
     * @return void
     */
    protected function checkColWidth($row): void
    {
        if (is_array($row)) {
            foreach ($row as $key => $cell) {
                $width = strlen((string) $cell);
                if (!isset($this->colWidth[$key]) || $width > $this->colWidth[$key]) {
                    $this->colWidth[$key] = $width;
                }
            }
        }
    }

    /**
     * ����һ�б������
     * @access public
     * @param  mixed $row       ������
     * @param  bool  $first     �Ƿ��ڿ�ͷ����
     * @return void
     */
    public function addRow($row, bool $first = false): void
    {
        if ($first) {
            array_unshift($this->rows, $row);
        } else {
            $this->rows[] = $row;
        }

        $this->checkColWidth($row);
    }

    /**
     * �������������ʽ
     * @access public
     * @param  string $style       ��ʽ��
     * @return void
     */
    public function setStyle(string $style): void
    {
        $this->style = isset($this->format[$style]) ? $style : 'default';
    }

    /**
     * ����ָ���
     * @access public
     * @param  string $pos       λ��
     * @return string
     */
    protected function renderSeparator(string $pos): string
    {
        $style = $this->getStyle($pos);
        $array = [];

        foreach ($this->colWidth as $width) {
            $array[] = str_repeat($style[1], $width + 2);
        }

        return $style[0] . implode($style[2], $array) . $style[3] . PHP_EOL;
    }

    /**
     * ������ͷ��
     * @access public
     * @return string
     */
    protected function renderHeader(): string
    {
        $style   = $this->getStyle('cell');
        $content = $this->renderSeparator('top');

        foreach ($this->header as $key => $header) {
            $array[] = ' ' . str_pad($header, $this->colWidth[$key], $style[1], $this->headerAlign);
        }

        if (!empty($array)) {
            $content .= $style[0] . implode(' ' . $style[2], $array) . ' ' . $style[3] . PHP_EOL;

            if (!empty($this->rows)) {
                $content .= $this->renderSeparator('middle');
            }
        }

        return $content;
    }

    protected function getStyle(string $style): array
    {
        if ($this->format[$this->style]) {
            $style = $this->format[$this->style][$style];
        } else {
            $style = [' ', ' ', ' ', ' '];
        }

        return $style;
    }

    /**
     * ������
     * @access public
     * @param  array $dataList       �������
     * @return string
     */
    public function render(array $dataList = []): string
    {
        if (!empty($dataList)) {
            $this->setRows($dataList);
        }

        // ���ͷ��
        $content = $this->renderHeader();
        $style   = $this->getStyle('cell');

        if (!empty($this->rows)) {
            foreach ($this->rows as $row) {
                if (is_string($row) && '-' === $row) {
                    $content .= $this->renderSeparator('middle');
                } elseif (is_scalar($row)) {
                    $content .= $this->renderSeparator('cross-top');
                    $array = str_pad($row, 3 * (count($this->colWidth) - 1) + array_reduce($this->colWidth, function ($a, $b) {
                        return $a + $b;
                    }));

                    $content .= $style[0] . ' ' . $array . ' ' . $style[3] . PHP_EOL;
                    $content .= $this->renderSeparator('cross-bottom');
                } else {
                    $array = [];

                    foreach ($row as $key => $val) {
                        $array[] = ' ' . str_pad((string) $val, $this->colWidth[$key], ' ', $this->cellAlign);
                    }

                    $content .= $style[0] . implode(' ' . $style[2], $array) . ' ' . $style[3] . PHP_EOL;

                }
            }
        }

        $content .= $this->renderSeparator('bottom');

        return $content;
    }
}
