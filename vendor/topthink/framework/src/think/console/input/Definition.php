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

namespace think\console\input;

class Definition
{

    /**
     * @var Argument[]
     */
    private $arguments;

    private $requiredCount;
    private $hasAnArrayArgument = false;
    private $hasOptional;

    /**
     * @var Option[]
     */
    private $options;
    private $shortcuts;

    /**
     * ���췽��
     * @param array $definition
     * @api
     */
    public function __construct(array $definition = [])
    {
        $this->setDefinition($definition);
    }

    /**
     * ����ָ��Ķ���
     * @param array $definition ���������
     */
    public function setDefinition(array $definition): void
    {
        $arguments = [];
        $options   = [];
        foreach ($definition as $item) {
            if ($item instanceof Option) {
                $options[] = $item;
            } else {
                $arguments[] = $item;
            }
        }

        $this->setArguments($arguments);
        $this->setOptions($options);
    }

    /**
     * ���ò���
     * @param Argument[] $arguments ��������
     */
    public function setArguments(array $arguments = []): void
    {
        $this->arguments          = [];
        $this->requiredCount      = 0;
        $this->hasOptional        = false;
        $this->hasAnArrayArgument = false;
        $this->addArguments($arguments);
    }

    /**
     * ��Ӳ���
     * @param Argument[] $arguments ��������
     * @api
     */
    public function addArguments(array $arguments = []): void
    {
        if (null !== $arguments) {
            foreach ($arguments as $argument) {
                $this->addArgument($argument);
            }
        }
    }

    /**
     * ���һ������
     * @param Argument $argument ����
     * @throws \LogicException
     */
    public function addArgument(Argument $argument): void
    {
        if (isset($this->arguments[$argument->getName()])) {
            throw new \LogicException(sprintf('An argument with name "%s" already exists.', $argument->getName()));
        }

        if ($this->hasAnArrayArgument) {
            throw new \LogicException('Cannot add an argument after an array argument.');
        }

        if ($argument->isRequired() && $this->hasOptional) {
            throw new \LogicException('Cannot add a required argument after an optional one.');
        }

        if ($argument->isArray()) {
            $this->hasAnArrayArgument = true;
        }

        if ($argument->isRequired()) {
            ++$this->requiredCount;
        } else {
            $this->hasOptional = true;
        }

        $this->arguments[$argument->getName()] = $argument;
    }

    /**
     * �������ƻ���λ�û�ȡ����
     * @param string|int $name ����������λ��
     * @return Argument ����
     * @throws \InvalidArgumentException
     */
    public function getArgument($name): Argument
    {
        if (!$this->hasArgument($name)) {
            throw new \InvalidArgumentException(sprintf('The "%s" argument does not exist.', $name));
        }

        $arguments = is_int($name) ? array_values($this->arguments) : $this->arguments;

        return $arguments[$name];
    }

    /**
     * �������ƻ�λ�ü���Ƿ����ĳ������
     * @param string|int $name ����������λ��
     * @return bool
     * @api
     */
    public function hasArgument($name): bool
    {
        $arguments = is_int($name) ? array_values($this->arguments) : $this->arguments;

        return isset($arguments[$name]);
    }

    /**
     * ��ȡ���еĲ���
     * @return Argument[] ��������
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * ��ȡ��������
     * @return int
     */
    public function getArgumentCount(): int
    {
        return $this->hasAnArrayArgument ? PHP_INT_MAX : count($this->arguments);
    }

    /**
     * ��ȡ����Ĳ���������
     * @return int
     */
    public function getArgumentRequiredCount(): int
    {
        return $this->requiredCount;
    }

    /**
     * ��ȡ����Ĭ��ֵ
     * @return array
     */
    public function getArgumentDefaults(): array
    {
        $values = [];
        foreach ($this->arguments as $argument) {
            $values[$argument->getName()] = $argument->getDefault();
        }

        return $values;
    }

    /**
     * ����ѡ��
     * @param Option[] $options ѡ������
     */
    public function setOptions(array $options = []): void
    {
        $this->options   = [];
        $this->shortcuts = [];
        $this->addOptions($options);
    }

    /**
     * ���ѡ��
     * @param Option[] $options ѡ������
     * @api
     */
    public function addOptions(array $options = []): void
    {
        foreach ($options as $option) {
            $this->addOption($option);
        }
    }

    /**
     * ���һ��ѡ��
     * @param Option $option ѡ��
     * @throws \LogicException
     * @api
     */
    public function addOption(Option $option): void
    {
        if (isset($this->options[$option->getName()]) && !$option->equals($this->options[$option->getName()])) {
            throw new \LogicException(sprintf('An option named "%s" already exists.', $option->getName()));
        }

        if ($option->getShortcut()) {
            foreach (explode('|', $option->getShortcut()) as $shortcut) {
                if (isset($this->shortcuts[$shortcut])
                    && !$option->equals($this->options[$this->shortcuts[$shortcut]])
                ) {
                    throw new \LogicException(sprintf('An option with shortcut "%s" already exists.', $shortcut));
                }
            }
        }

        $this->options[$option->getName()] = $option;
        if ($option->getShortcut()) {
            foreach (explode('|', $option->getShortcut()) as $shortcut) {
                $this->shortcuts[$shortcut] = $option->getName();
            }
        }
    }

    /**
     * �������ƻ�ȡѡ��
     * @param string $name ѡ����
     * @return Option
     * @throws \InvalidArgumentException
     * @api
     */
    public function getOption(string $name): Option
    {
        if (!$this->hasOption($name)) {
            throw new \InvalidArgumentException(sprintf('The "--%s" option does not exist.', $name));
        }

        return $this->options[$name];
    }

    /**
     * �������Ƽ���Ƿ������ѡ��
     * @param string $name ѡ����
     * @return bool
     * @api
     */
    public function hasOption(string $name): bool
    {
        return isset($this->options[$name]);
    }

    /**
     * ��ȡ����ѡ��
     * @return Option[]
     * @api
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * �������Ƽ��ĳ��ѡ���Ƿ��ж�����
     * @param string $name ������
     * @return bool
     */
    public function hasShortcut(string $name): bool
    {
        return isset($this->shortcuts[$name]);
    }

    /**
     * ���ݶ����ƻ�ȡѡ��
     * @param string $shortcut ������
     * @return Option
     */
    public function getOptionForShortcut(string $shortcut): Option
    {
        return $this->getOption($this->shortcutToName($shortcut));
    }

    /**
     * ��ȡ����ѡ���Ĭ��ֵ
     * @return array
     */
    public function getOptionDefaults(): array
    {
        $values = [];
        foreach ($this->options as $option) {
            $values[$option->getName()] = $option->getDefault();
        }

        return $values;
    }

    /**
     * ���ݶ����ƻ�ȡѡ����
     * @param string $shortcut ������
     * @return string
     * @throws \InvalidArgumentException
     */
    private function shortcutToName(string $shortcut): string
    {
        if (!isset($this->shortcuts[$shortcut])) {
            throw new \InvalidArgumentException(sprintf('The "-%s" option does not exist.', $shortcut));
        }

        return $this->shortcuts[$shortcut];
    }

    /**
     * ��ȡ��ָ��Ľ���
     * @param bool $short �Ƿ������
     * @return string
     */
    public function getSynopsis(bool $short = false): string
    {
        $elements = [];

        if ($short && $this->getOptions()) {
            $elements[] = '[options]';
        } elseif (!$short) {
            foreach ($this->getOptions() as $option) {
                $value = '';
                if ($option->acceptValue()) {
                    $value = sprintf(' %s%s%s', $option->isValueOptional() ? '[' : '', strtoupper($option->getName()), $option->isValueOptional() ? ']' : '');
                }

                $shortcut   = $option->getShortcut() ? sprintf('-%s|', $option->getShortcut()) : '';
                $elements[] = sprintf('[%s--%s%s]', $shortcut, $option->getName(), $value);
            }
        }

        if (count($elements) && $this->getArguments()) {
            $elements[] = '[--]';
        }

        foreach ($this->getArguments() as $argument) {
            $element = '<' . $argument->getName() . '>';
            if (!$argument->isRequired()) {
                $element = '[' . $element . ']';
            } elseif ($argument->isArray()) {
                $element .= ' (' . $element . ')';
            }

            if ($argument->isArray()) {
                $element .= '...';
            }

            $elements[] = $element;
        }

        return implode(' ', $elements);
    }
}
