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

class Argument
{

    const REQUIRED = 1;
    const OPTIONAL = 2;
    const IS_ARRAY = 4;

    private $name;
    private $mode;
    private $default;
    private $description;

    /**
     * ���췽��
     * @param string $name        ������
     * @param int    $mode        ��������: self::REQUIRED ���� self::OPTIONAL
     * @param string $description ����
     * @param mixed  $default     Ĭ��ֵ (�� self::OPTIONAL ������Ч)
     * @throws \InvalidArgumentException
     */
    public function __construct(string $name, int $mode = null, string $description = '', $default = null)
    {
        if (null === $mode) {
            $mode = self::OPTIONAL;
        } elseif (!is_int($mode) || $mode > 7 || $mode < 1) {
            throw new \InvalidArgumentException(sprintf('Argument mode "%s" is not valid.', $mode));
        }

        $this->name        = $name;
        $this->mode        = $mode;
        $this->description = $description;

        $this->setDefault($default);
    }

    /**
     * ��ȡ������
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * �Ƿ����
     * @return bool
     */
    public function isRequired(): bool
    {
        return self::REQUIRED === (self::REQUIRED & $this->mode);
    }

    /**
     * �ò����Ƿ��������
     * @return bool
     */
    public function isArray(): bool
    {
        return self::IS_ARRAY === (self::IS_ARRAY & $this->mode);
    }

    /**
     * ����Ĭ��ֵ
     * @param mixed $default Ĭ��ֵ
     * @throws \LogicException
     */
    public function setDefault($default = null): void
    {
        if (self::REQUIRED === $this->mode && null !== $default) {
            throw new \LogicException('Cannot set a default value except for InputArgument::OPTIONAL mode.');
        }

        if ($this->isArray()) {
            if (null === $default) {
                $default = [];
            } elseif (!is_array($default)) {
                throw new \LogicException('A default value for an array argument must be an array.');
            }
        }

        $this->default = $default;
    }

    /**
     * ��ȡĬ��ֵ
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * ��ȡ����
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }
}
