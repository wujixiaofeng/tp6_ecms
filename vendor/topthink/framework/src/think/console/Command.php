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
use InvalidArgumentException;
use LogicException;
use think\App;
use think\Console;
use think\console\input\Argument;
use think\console\input\Definition;
use think\console\input\Option;

abstract class Command
{

    /** @var  Console */
    private $console;
    private $name;
    private $processTitle;
    private $aliases                         = [];
    private $definition;
    private $help;
    private $description;
    private $ignoreValidationErrors          = false;
    private $consoleDefinitionMerged         = false;
    private $consoleDefinitionMergedWithArgs = false;
    private $synopsis                        = [];
    private $usages                          = [];

    /** @var  Input */
    protected $input;

    /** @var  Output */
    protected $output;

    /** @var App */
    protected $app;

    /**
     * ���췽��
     * @throws LogicException
     * @api
     */
    public function __construct()
    {
        $this->definition = new Definition();

        $this->configure();

        if (!$this->name) {
            throw new LogicException(sprintf('The command defined in "%s" cannot have an empty name.', get_class($this)));
        }
    }

    /**
     * ������֤����
     */
    public function ignoreValidationErrors(): void
    {
        $this->ignoreValidationErrors = true;
    }

    /**
     * ���ÿ���̨
     * @param Console $console
     */
    public function setConsole(Console $console = null): void
    {
        $this->console = $console;
    }

    /**
     * ��ȡ����̨
     * @return Console
     * @api
     */
    public function getConsole(): Console
    {
        return $this->console;
    }

    /**
     * ����app
     * @param App $app
     */
    public function setApp(App $app)
    {
        $this->app = $app;
    }

    /**
     * ��ȡapp
     * @return App
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * �Ƿ���Ч
     * @return bool
     */
    public function isEnabled(): bool
    {
        return true;
    }

    /**
     * ����ָ��
     */
    protected function configure()
    {
    }

    /**
     * ִ��ָ��
     * @param Input  $input
     * @param Output $output
     * @return null|int
     * @throws LogicException
     * @see setCode()
     */
    protected function execute(Input $input, Output $output)
    {
        return $this->app->invoke([$this, 'handle']);
    }

    /**
     * �û���֤
     * @param Input  $input
     * @param Output $output
     */
    protected function interact(Input $input, Output $output)
    {
    }

    /**
     * ��ʼ��
     * @param Input  $input  An InputInterface instance
     * @param Output $output An OutputInterface instance
     */
    protected function initialize(Input $input, Output $output)
    {
    }

    /**
     * ִ��
     * @param Input  $input
     * @param Output $output
     * @return int
     * @throws Exception
     * @see setCode()
     * @see execute()
     */
    public function run(Input $input, Output $output): int
    {
        $this->input  = $input;
        $this->output = $output;

        $this->getSynopsis(true);
        $this->getSynopsis(false);

        $this->mergeConsoleDefinition();

        try {
            $input->bind($this->definition);
        } catch (Exception $e) {
            if (!$this->ignoreValidationErrors) {
                throw $e;
            }
        }

        $this->initialize($input, $output);

        if (null !== $this->processTitle) {
            if (function_exists('cli_set_process_title')) {
                if (false === @cli_set_process_title($this->processTitle)) {
                    if ('Darwin' === PHP_OS) {
                        $output->writeln('<comment>Running "cli_get_process_title" as an unprivileged user is not supported on MacOS.</comment>');
                    } else {
                        $error = error_get_last();
                        trigger_error($error['message'], E_USER_WARNING);
                    }
                }
            } elseif (function_exists('setproctitle')) {
                setproctitle($this->processTitle);
            } elseif (Output::VERBOSITY_VERY_VERBOSE === $output->getVerbosity()) {
                $output->writeln('<comment>Install the proctitle PECL to be able to change the process title.</comment>');
            }
        }

        if ($input->isInteractive()) {
            $this->interact($input, $output);
        }

        $input->validate();

        $statusCode = $this->execute($input, $output);

        return is_numeric($statusCode) ? (int) $statusCode : 0;
    }

    /**
     * �ϲ���������
     * @param bool $mergeArgs
     */
    public function mergeConsoleDefinition(bool $mergeArgs = true)
    {
        if (null === $this->console
            || (true === $this->consoleDefinitionMerged
                && ($this->consoleDefinitionMergedWithArgs || !$mergeArgs))
        ) {
            return;
        }

        if ($mergeArgs) {
            $currentArguments = $this->definition->getArguments();
            $this->definition->setArguments($this->console->getDefinition()->getArguments());
            $this->definition->addArguments($currentArguments);
        }

        $this->definition->addOptions($this->console->getDefinition()->getOptions());

        $this->consoleDefinitionMerged = true;
        if ($mergeArgs) {
            $this->consoleDefinitionMergedWithArgs = true;
        }
    }

    /**
     * ���ò�������
     * @param array|Definition $definition
     * @return Command
     * @api
     */
    public function setDefinition($definition)
    {
        if ($definition instanceof Definition) {
            $this->definition = $definition;
        } else {
            $this->definition->setDefinition($definition);
        }

        $this->consoleDefinitionMerged = false;

        return $this;
    }

    /**
     * ��ȡ��������
     * @return Definition
     * @api
     */
    public function getDefinition(): Definition
    {
        return $this->definition;
    }

    /**
     * ��ȡ��ǰָ��Ĳ�������
     * @return Definition
     */
    public function getNativeDefinition(): Definition
    {
        return $this->getDefinition();
    }

    /**
     * ��Ӳ���
     * @param string $name        ����
     * @param int    $mode        ����
     * @param string $description ����
     * @param mixed  $default     Ĭ��ֵ
     * @return Command
     */
    public function addArgument(string $name, int $mode = null, string $description = '', $default = null)
    {
        $this->definition->addArgument(new Argument($name, $mode, $description, $default));

        return $this;
    }

    /**
     * ���ѡ��
     * @param string $name        ѡ������
     * @param string $shortcut    ����
     * @param int    $mode        ����
     * @param string $description ����
     * @param mixed  $default     Ĭ��ֵ
     * @return Command
     */
    public function addOption(string $name, string $shortcut = null, int $mode = null, string $description = '', $default = null)
    {
        $this->definition->addOption(new Option($name, $shortcut, $mode, $description, $default));

        return $this;
    }

    /**
     * ����ָ������
     * @param string $name
     * @return Command
     * @throws InvalidArgumentException
     */
    public function setName(string $name)
    {
        $this->validateName($name);

        $this->name = $name;

        return $this;
    }

    /**
     * ���ý�������
     *
     * PHP 5.5+ or the proctitle PECL library is required
     *
     * @param string $title The process title
     *
     * @return $this
     */
    public function setProcessTitle($title)
    {
        $this->processTitle = $title;

        return $this;
    }

    /**
     * ��ȡָ������
     * @return string
     */
    public function getName(): string
    {
        return $this->name ?: '';
    }

    /**
     * ��������
     * @param string $description
     * @return Command
     */
    public function setDescription(string $description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     *  ��ȡ����
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description ?: '';
    }

    /**
     * ���ð�����Ϣ
     * @param string $help
     * @return Command
     */
    public function setHelp(string $help)
    {
        $this->help = $help;

        return $this;
    }

    /**
     * ��ȡ������Ϣ
     * @return string
     */
    public function getHelp(): string
    {
        return $this->help ?: '';
    }

    /**
     * ������Ϣ
     * @return string
     */
    public function getProcessedHelp(): string
    {
        $name = $this->name;

        $placeholders = [
            '%command.name%',
            '%command.full_name%',
        ];
        $replacements = [
            $name,
            $_SERVER['PHP_SELF'] . ' ' . $name,
        ];

        return str_replace($placeholders, $replacements, $this->getHelp());
    }

    /**
     * ���ñ���
     * @param string[] $aliases
     * @return Command
     * @throws InvalidArgumentException
     */
    public function setAliases(iterable $aliases)
    {
        foreach ($aliases as $alias) {
            $this->validateName($alias);
        }

        $this->aliases = $aliases;

        return $this;
    }

    /**
     * ��ȡ����
     * @return array
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * ��ȡ���
     * @param bool $short �Ƿ�򵥵�
     * @return string
     */
    public function getSynopsis(bool $short = false): string
    {
        $key = $short ? 'short' : 'long';

        if (!isset($this->synopsis[$key])) {
            $this->synopsis[$key] = trim(sprintf('%s %s', $this->name, $this->definition->getSynopsis($short)));
        }

        return $this->synopsis[$key];
    }

    /**
     * ����÷�����
     * @param string $usage
     * @return $this
     */
    public function addUsage(string $usage)
    {
        if (0 !== strpos($usage, $this->name)) {
            $usage = sprintf('%s %s', $this->name, $usage);
        }

        $this->usages[] = $usage;

        return $this;
    }

    /**
     * ��ȡ�÷�����
     * @return array
     */
    public function getUsages(): array
    {
        return $this->usages;
    }

    /**
     * ��ָ֤������
     * @param string $name
     * @throws InvalidArgumentException
     */
    private function validateName(string $name)
    {
        if (!preg_match('/^[^\:]++(\:[^\:]++)*$/', $name)) {
            throw new InvalidArgumentException(sprintf('Command name "%s" is invalid.', $name));
        }
    }

    /**
     * ������
     * @param Table $table
     * @return string
     */
    protected function table(Table $table): string
    {
        $content = $table->render();
        $this->output->writeln($content);
        return $content;
    }
}
