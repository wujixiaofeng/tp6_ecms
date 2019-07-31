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

use think\console\input\Argument;
use think\console\input\Definition;
use think\console\input\Option;

class Input
{

    /**
     * @var Definition
     */
    protected $definition;

    /**
     * @var Option[]
     */
    protected $options = [];

    /**
     * @var Argument[]
     */
    protected $arguments = [];

    protected $interactive = true;

    private $tokens;
    private $parsed;

    public function __construct($argv = null)
    {
        if (null === $argv) {
            $argv = $_SERVER['argv'];
            // ȥ��������
            array_shift($argv);
        }

        $this->tokens = $argv;

        $this->definition = new Definition();
    }

    protected function setTokens(array $tokens)
    {
        $this->tokens = $tokens;
    }

    /**
     * ��ʵ��
     * @param Definition $definition A InputDefinition instance
     */
    public function bind(Definition $definition): void
    {
        $this->arguments  = [];
        $this->options    = [];
        $this->definition = $definition;

        $this->parse();
    }

    /**
     * ��������
     */
    protected function parse(): void
    {
        $parseOptions = true;
        $this->parsed = $this->tokens;
        while (null !== $token = array_shift($this->parsed)) {
            if ($parseOptions && '' == $token) {
                $this->parseArgument($token);
            } elseif ($parseOptions && '--' == $token) {
                $parseOptions = false;
            } elseif ($parseOptions && 0 === strpos($token, '--')) {
                $this->parseLongOption($token);
            } elseif ($parseOptions && '-' === $token[0] && '-' !== $token) {
                $this->parseShortOption($token);
            } else {
                $this->parseArgument($token);
            }
        }
    }

    /**
     * ������ѡ��
     * @param string $token ��ǰ��ָ��.
     */
    private function parseShortOption(string $token): void
    {
        $name = substr($token, 1);

        if (strlen($name) > 1) {
            if ($this->definition->hasShortcut($name[0])
                && $this->definition->getOptionForShortcut($name[0])->acceptValue()
            ) {
                $this->addShortOption($name[0], substr($name, 1));
            } else {
                $this->parseShortOptionSet($name);
            }
        } else {
            $this->addShortOption($name, null);
        }
    }

    /**
     * ������ѡ��
     * @param string $name ��ǰָ��
     * @throws \RuntimeException
     */
    private function parseShortOptionSet(string $name): void
    {
        $len = strlen($name);
        for ($i = 0; $i < $len; ++$i) {
            if (!$this->definition->hasShortcut($name[$i])) {
                throw new \RuntimeException(sprintf('The "-%s" option does not exist.', $name[$i]));
            }

            $option = $this->definition->getOptionForShortcut($name[$i]);
            if ($option->acceptValue()) {
                $this->addLongOption($option->getName(), $i === $len - 1 ? null : substr($name, $i + 1));

                break;
            } else {
                $this->addLongOption($option->getName(), null);
            }
        }
    }

    /**
     * ��������ѡ��
     * @param string $token ��ǰָ��
     */
    private function parseLongOption(string $token): void
    {
        $name = substr($token, 2);

        if (false !== $pos = strpos($name, '=')) {
            $this->addLongOption(substr($name, 0, $pos), substr($name, $pos + 1));
        } else {
            $this->addLongOption($name, null);
        }
    }

    /**
     * ��������
     * @param string $token ��ǰָ��
     * @throws \RuntimeException
     */
    private function parseArgument(string $token): void
    {
        $c = count($this->arguments);

        if ($this->definition->hasArgument($c)) {
            $arg = $this->definition->getArgument($c);

            $this->arguments[$arg->getName()] = $arg->isArray() ? [$token] : $token;

        } elseif ($this->definition->hasArgument($c - 1) && $this->definition->getArgument($c - 1)->isArray()) {
            $arg = $this->definition->getArgument($c - 1);

            $this->arguments[$arg->getName()][] = $token;
        } else {
            throw new \RuntimeException('Too many arguments.');
        }
    }

    /**
     * ���һ����ѡ���ֵ
     * @param string $shortcut ������
     * @param mixed  $value    ֵ
     * @throws \RuntimeException
     */
    private function addShortOption(string $shortcut, $value): void
    {
        if (!$this->definition->hasShortcut($shortcut)) {
            throw new \RuntimeException(sprintf('The "-%s" option does not exist.', $shortcut));
        }

        $this->addLongOption($this->definition->getOptionForShortcut($shortcut)->getName(), $value);
    }

    /**
     * ���һ������ѡ���ֵ
     * @param string $name  ѡ����
     * @param mixed  $value ֵ
     * @throws \RuntimeException
     */
    private function addLongOption(string $name, $value): void
    {
        if (!$this->definition->hasOption($name)) {
            throw new \RuntimeException(sprintf('The "--%s" option does not exist.', $name));
        }

        $option = $this->definition->getOption($name);

        if (false === $value) {
            $value = null;
        }

        if (null !== $value && !$option->acceptValue()) {
            throw new \RuntimeException(sprintf('The "--%s" option does not accept a value.', $name, $value));
        }

        if (null === $value && $option->acceptValue() && count($this->parsed)) {
            $next = array_shift($this->parsed);
            if (isset($next[0]) && '-' !== $next[0]) {
                $value = $next;
            } elseif (empty($next)) {
                $value = '';
            } else {
                array_unshift($this->parsed, $next);
            }
        }

        if (null === $value) {
            if ($option->isValueRequired()) {
                throw new \RuntimeException(sprintf('The "--%s" option requires a value.', $name));
            }

            if (!$option->isArray()) {
                $value = $option->isValueOptional() ? $option->getDefault() : true;
            }
        }

        if ($option->isArray()) {
            $this->options[$name][] = $value;
        } else {
            $this->options[$name] = $value;
        }
    }

    /**
     * ��ȡ��һ������
     * @return string|null
     */
    public function getFirstArgument()
    {
        foreach ($this->tokens as $token) {
            if ($token && '-' === $token[0]) {
                continue;
            }

            return $token;
        }
        return;
    }

    /**
     * ���ԭʼ�����Ƿ����ĳ��ֵ
     * @param string|array $values ��Ҫ����ֵ
     * @return bool
     */
    public function hasParameterOption($values): bool
    {
        $values = (array) $values;

        foreach ($this->tokens as $token) {
            foreach ($values as $value) {
                if ($token === $value || 0 === strpos($token, $value . '=')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * ��ȡԭʼѡ���ֵ
     * @param string|array $values  ��Ҫ����ֵ
     * @param mixed        $default Ĭ��ֵ
     * @return mixed The option value
     */
    public function getParameterOption($values, $default = false)
    {
        $values = (array) $values;
        $tokens = $this->tokens;

        while (0 < count($tokens)) {
            $token = array_shift($tokens);

            foreach ($values as $value) {
                if ($token === $value || 0 === strpos($token, $value . '=')) {
                    if (false !== $pos = strpos($token, '=')) {
                        return substr($token, $pos + 1);
                    }

                    return array_shift($tokens);
                }
            }
        }

        return $default;
    }

    /**
     * ��֤����
     * @throws \RuntimeException
     */
    public function validate()
    {
        if (count($this->arguments) < $this->definition->getArgumentRequiredCount()) {
            throw new \RuntimeException('Not enough arguments.');
        }
    }

    /**
     * ��������Ƿ��ǽ�����
     * @return bool
     */
    public function isInteractive(): bool
    {
        return $this->interactive;
    }

    /**
     * ��������Ľ���
     * @param bool
     */
    public function setInteractive(bool $interactive): void
    {
        $this->interactive = $interactive;
    }

    /**
     * ��ȡ���еĲ���
     * @return Argument[]
     */
    public function getArguments(): array
    {
        return array_merge($this->definition->getArgumentDefaults(), $this->arguments);
    }

    /**
     * �������ƻ�ȡ����
     * @param string $name ������
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function getArgument(string $name)
    {
        if (!$this->definition->hasArgument($name)) {
            throw new \InvalidArgumentException(sprintf('The "%s" argument does not exist.', $name));
        }

        return $this->arguments[$name] ?? $this->definition->getArgument($name)
            ->getDefault();
    }

    /**
     * ���ò�����ֵ
     * @param string $name  ������
     * @param string $value ֵ
     * @throws \InvalidArgumentException
     */
    public function setArgument(string $name, $value)
    {
        if (!$this->definition->hasArgument($name)) {
            throw new \InvalidArgumentException(sprintf('The "%s" argument does not exist.', $name));
        }

        $this->arguments[$name] = $value;
    }

    /**
     * ����Ƿ����ĳ������
     * @param string|int $name ��������λ��
     * @return bool
     */
    public function hasArgument($name): bool
    {
        return $this->definition->hasArgument($name);
    }

    /**
     * ��ȡ���е�ѡ��
     * @return Option[]
     */
    public function getOptions(): array
    {
        return array_merge($this->definition->getOptionDefaults(), $this->options);
    }

    /**
     * ��ȡѡ��ֵ
     * @param string $name ѡ������
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function getOption(string $name)
    {
        if (!$this->definition->hasOption($name)) {
            throw new \InvalidArgumentException(sprintf('The "%s" option does not exist.', $name));
        }

        return $this->options[$name] ?? $this->definition->getOption($name)->getDefault();
    }

    /**
     * ����ѡ��ֵ
     * @param string      $name  ѡ����
     * @param string|bool $value ֵ
     * @throws \InvalidArgumentException
     */
    public function setOption(string $name, $value): void
    {
        if (!$this->definition->hasOption($name)) {
            throw new \InvalidArgumentException(sprintf('The "%s" option does not exist.', $name));
        }

        $this->options[$name] = $value;
    }

    /**
     * �Ƿ���ĳ��ѡ��
     * @param string $name ѡ����
     * @return bool
     */
    public function hasOption(string $name): bool
    {
        return $this->definition->hasOption($name) && isset($this->options[$name]);
    }

    /**
     * ת��ָ��
     * @param string $token
     * @return string
     */
    public function escapeToken(string $token): string
    {
        return preg_match('{^[\w-]+$}', $token) ? $token : escapeshellarg($token);
    }

    /**
     * ���ش��ݸ�����Ĳ������ַ���
     * @return string
     */
    public function __toString()
    {
        $tokens = array_map(function ($token) {
            if (preg_match('{^(-[^=]+=)(.+)}', $token, $match)) {
                return $match[1] . $this->escapeToken($match[2]);
            }

            if ($token && '-' !== $token[0]) {
                return $this->escapeToken($token);
            }

            return $token;
        }, $this->tokens);

        return implode(' ', $tokens);
    }
}
