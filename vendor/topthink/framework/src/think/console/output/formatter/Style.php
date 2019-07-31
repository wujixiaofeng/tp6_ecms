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

namespace think\console\output\formatter;

class Style
{
    protected static $availableForegroundColors = [
        'black'   => ['set' => 30, 'unset' => 39],
        'red'     => ['set' => 31, 'unset' => 39],
        'green'   => ['set' => 32, 'unset' => 39],
        'yellow'  => ['set' => 33, 'unset' => 39],
        'blue'    => ['set' => 34, 'unset' => 39],
        'magenta' => ['set' => 35, 'unset' => 39],
        'cyan'    => ['set' => 36, 'unset' => 39],
        'white'   => ['set' => 37, 'unset' => 39],
    ];

    protected static $availableBackgroundColors = [
        'black'   => ['set' => 40, 'unset' => 49],
        'red'     => ['set' => 41, 'unset' => 49],
        'green'   => ['set' => 42, 'unset' => 49],
        'yellow'  => ['set' => 43, 'unset' => 49],
        'blue'    => ['set' => 44, 'unset' => 49],
        'magenta' => ['set' => 45, 'unset' => 49],
        'cyan'    => ['set' => 46, 'unset' => 49],
        'white'   => ['set' => 47, 'unset' => 49],
    ];

    protected static $availableOptions = [
        'bold'       => ['set' => 1, 'unset' => 22],
        'underscore' => ['set' => 4, 'unset' => 24],
        'blink'      => ['set' => 5, 'unset' => 25],
        'reverse'    => ['set' => 7, 'unset' => 27],
        'conceal'    => ['set' => 8, 'unset' => 28],
    ];

    private $foreground;
    private $background;
    private $options = [];

    /**
     * ��ʼ���������ʽ
     * @param string|null $foreground ������ɫ
     * @param string|null $background ����ɫ
     * @param array       $options    ��ʽ
     * @api
     */
    public function __construct($foreground = null, $background = null, array $options = [])
    {
        if (null !== $foreground) {
            $this->setForeground($foreground);
        }
        if (null !== $background) {
            $this->setBackground($background);
        }
        if (count($options)) {
            $this->setOptions($options);
        }
    }

    /**
     * ����������ɫ
     * @param string|null $color ��ɫ��
     * @throws \InvalidArgumentException
     * @api
     */
    public function setForeground($color = null)
    {
        if (null === $color) {
            $this->foreground = null;

            return;
        }

        if (!isset(static::$availableForegroundColors[$color])) {
            throw new \InvalidArgumentException(sprintf('Invalid foreground color specified: "%s". Expected one of (%s)', $color, implode(', ', array_keys(static::$availableForegroundColors))));
        }

        $this->foreground = static::$availableForegroundColors[$color];
    }

    /**
     * ���ñ���ɫ
     * @param string|null $color ��ɫ��
     * @throws \InvalidArgumentException
     * @api
     */
    public function setBackground($color = null)
    {
        if (null === $color) {
            $this->background = null;

            return;
        }

        if (!isset(static::$availableBackgroundColors[$color])) {
            throw new \InvalidArgumentException(sprintf('Invalid background color specified: "%s". Expected one of (%s)', $color, implode(', ', array_keys(static::$availableBackgroundColors))));
        }

        $this->background = static::$availableBackgroundColors[$color];
    }

    /**
     * ���������ʽ
     * @param string $option ��ʽ��
     * @throws \InvalidArgumentException When the option name isn't defined
     * @api
     */
    public function setOption(string $option): void
    {
        if (!isset(static::$availableOptions[$option])) {
            throw new \InvalidArgumentException(sprintf('Invalid option specified: "%s". Expected one of (%s)', $option, implode(', ', array_keys(static::$availableOptions))));
        }

        if (!in_array(static::$availableOptions[$option], $this->options)) {
            $this->options[] = static::$availableOptions[$option];
        }
    }

    /**
     * ���������ʽ
     * @param string $option ��ʽ��
     * @throws \InvalidArgumentException
     */
    public function unsetOption(string $option): void
    {
        if (!isset(static::$availableOptions[$option])) {
            throw new \InvalidArgumentException(sprintf('Invalid option specified: "%s". Expected one of (%s)', $option, implode(', ', array_keys(static::$availableOptions))));
        }

        $pos = array_search(static::$availableOptions[$option], $this->options);
        if (false !== $pos) {
            unset($this->options[$pos]);
        }
    }

    /**
     * �������������ʽ
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = [];

        foreach ($options as $option) {
            $this->setOption($option);
        }
    }

    /**
     * Ӧ����ʽ������
     * @param string $text ����
     * @return string
     */
    public function apply(string $text): string
    {
        $setCodes   = [];
        $unsetCodes = [];

        if (null !== $this->foreground) {
            $setCodes[]   = $this->foreground['set'];
            $unsetCodes[] = $this->foreground['unset'];
        }
        if (null !== $this->background) {
            $setCodes[]   = $this->background['set'];
            $unsetCodes[] = $this->background['unset'];
        }
        if (count($this->options)) {
            foreach ($this->options as $option) {
                $setCodes[]   = $option['set'];
                $unsetCodes[] = $option['unset'];
            }
        }

        if (0 === count($setCodes)) {
            return $text;
        }

        return sprintf("\033[%sm%s\033[%sm", implode(';', $setCodes), $text, implode(';', $unsetCodes));
    }
}
