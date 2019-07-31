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

use Exception;
use think\console\output\Ask;
use think\console\output\Descriptor;
use think\console\output\driver\Buffer;
use think\console\output\driver\Console;
use think\console\output\driver\Nothing;
use think\console\output\Question;
use think\console\output\question\Choice;
use think\console\output\question\Confirmation;
use Throwable;

/**
 * Class Output
 * @package think\console
 *
 * @see     \think\console\output\driver\Console::setDecorated
 * @method void setDecorated($decorated)
 *
 * @see     \think\console\output\driver\Buffer::fetch
 * @method string fetch()
 *
 * @method void info($message)
 * @method void error($message)
 * @method void comment($message)
 * @method void warning($message)
 * @method void highlight($message)
 * @method void question($message)
 */
class Output
{
    const VERBOSITY_QUIET        = 0;
    const VERBOSITY_NORMAL       = 1;
    const VERBOSITY_VERBOSE      = 2;
    const VERBOSITY_VERY_VERBOSE = 3;
    const VERBOSITY_DEBUG        = 4;

    const OUTPUT_NORMAL = 0;
    const OUTPUT_RAW    = 1;
    const OUTPUT_PLAIN  = 2;

    private $verbosity = self::VERBOSITY_NORMAL;

    /** @var Buffer|Console|Nothing */
    private $handle = null;

    protected $styles = [
        'info',
        'error',
        'comment',
        'question',
        'highlight',
        'warning',
    ];

    public function __construct($driver = 'console')
    {
        $class = '\\think\\console\\output\\driver\\' . ucwords($driver);

        $this->handle = new $class($this);
    }

    public function ask(Input $input, $question, $default = null, $validator = null)
    {
        $question = new Question($question, $default);
        $question->setValidator($validator);

        return $this->askQuestion($input, $question);
    }

    public function askHidden(Input $input, $question, $validator = null)
    {
        $question = new Question($question);

        $question->setHidden(true);
        $question->setValidator($validator);

        return $this->askQuestion($input, $question);
    }

    public function confirm(Input $input, $question, $default = true)
    {
        return $this->askQuestion($input, new Confirmation($question, $default));
    }

    /**
     * {@inheritdoc}
     */
    public function choice(Input $input, $question, array $choices, $default = null)
    {
        if (null !== $default) {
            $values  = array_flip($choices);
            $default = $values[$default];
        }

        return $this->askQuestion($input, new Choice($question, $choices, $default));
    }

    protected function askQuestion(Input $input, Question $question)
    {
        $ask    = new Ask($input, $this, $question);
        $answer = $ask->run();

        if ($input->isInteractive()) {
            $this->newLine();
        }

        return $answer;
    }

    protected function block(string $style, string $message): void
    {
        $this->writeln("<{$style}>{$message}</$style>");
    }

    /**
     * �������
     * @param int $count
     */
    public function newLine(int $count = 1): void
    {
        $this->write(str_repeat(PHP_EOL, $count));
    }

    /**
     * �����Ϣ������
     * @param string $messages
     * @param int    $type
     */
    public function writeln(string $messages, int $type = 0): void
    {
        $this->write($messages, true, $type);
    }

    /**
     * �����Ϣ
     * @param string $messages
     * @param bool   $newline
     * @param int    $type
     */
    public function write(string $messages, bool $newline = false, int $type = 0): void
    {
        $this->handle->write($messages, $newline, $type);
    }

    public function renderException(Throwable $e): void
    {
        $this->handle->renderException($e);
    }

    /**
     * {@inheritdoc}
     */
    public function setVerbosity(int $level)
    {
        $this->verbosity = $level;
    }

    /**
     * {@inheritdoc}
     */
    public function getVerbosity(): int
    {
        return $this->verbosity;
    }

    public function isQuiet(): bool
    {
        return self::VERBOSITY_QUIET === $this->verbosity;
    }

    public function isVerbose(): bool
    {
        return self::VERBOSITY_VERBOSE <= $this->verbosity;
    }

    public function isVeryVerbose(): bool
    {
        return self::VERBOSITY_VERY_VERBOSE <= $this->verbosity;
    }

    public function isDebug(): bool
    {
        return self::VERBOSITY_DEBUG <= $this->verbosity;
    }

    public function describe($object, array $options = []): void
    {
        $descriptor = new Descriptor();
        $options    = array_merge([
            'raw_text' => false,
        ], $options);

        $descriptor->describe($this, $object, $options);
    }

    public function __call($method, $args)
    {
        if (in_array($method, $this->styles)) {
            array_unshift($args, $method);
            return call_user_func_array([$this, 'block'], $args);
        }

        if ($this->handle && method_exists($this->handle, $method)) {
            return call_user_func_array([$this->handle, $method], $args);
        } else {
            throw new Exception('method not exists:' . __CLASS__ . '->' . $method);
        }
    }

}