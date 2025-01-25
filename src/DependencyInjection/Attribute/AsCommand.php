<?php

namespace Kaluzki\DerReeder\DependencyInjection\Attribute;

use Kaluzki\DerReeder\DependencyInjection\ValueResolver;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Cursor;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadataFactory;
use Symfony\Component\Process\Process;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class AsCommand extends \Symfony\Component\Console\Attribute\AsCommand
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        array $aliases = [],
        bool $hidden = false,
        public readonly ?array $process = null
    ) {
        parent::__construct($name, $this->description, $aliases, $hidden);
    }

    public static function passHandler(
        self $attr,
        ContainerBuilder $container,
        \ReflectionMethod|\ReflectionClass $reflector,
        string $id,
    ): void {
        $reflector instanceof \ReflectionClass && $reflector = $reflector->getMethod('__invoke');
        $commandId = $attr::class . ".$id::{$reflector->getName()}";

        $command = $container->register($commandId, Command::class)
            ->addTag('console.command', [
                'command' => implode('|', [$attr->name]),
                'description' => $attr->description ?? ' '
            ])->addMethodCall('setCode', [
                new Definition(arguments: [
                    [new Reference($id), $reflector->getName()],
                    new Reference($commandId),
                    $attr->process,
                ])->setFactory([static::class, 'createCode'])
            ]);

        foreach (self::meta($reflector) as $arg) {
            if (!in_array($arg->getType(), ['string', 'int'])) {
                continue;
            }
            /* @uses Command::addArgument() */
            $command->addMethodCall('addArgument', [
                $arg->getName(),
                ($arg->hasDefaultValue() ? InputArgument::OPTIONAL : InputArgument::REQUIRED),
                '',
                $arg->hasDefaultValue() ? $arg->getDefaultValue() : null
            ]);
        }
    }

    public static function createCode(callable $code, Command $command, ?array $process = null): callable
    {
        /** @var ?Application $app */
        ($app = $command->getApplication()) instanceof Application || $app = null;
        if ($process && !getenv($varName = md5(__METHOD__))) {
            return self::subProcess($_SERVER['argv'] ?? [], $process, $varName);
        }
        return function (InputInterface $input, OutputInterface $output) use ($code, $app) {
            $resolver = self::resolver($input, $output, $app);
            $args = $resolver->getArguments(new Request(), $code);
            $return = $code(...$args);
            foreach (is_iterable($return) ? $return : [] as $line) {
                $output->writeln($line);
            }
            return is_int($return) ? $return : Command::SUCCESS;
        };
    }

    private static function subProcess(array $command, array $config, string $varName): callable
    {
        return function () use ($varName, $command, $config) {
            $config += [
                'tty' => true,
                'pty' => true,
                'options' => ['create_new_console' => true],
                'restore_stty' => true,
                'alternate_screen' => false, // https://unix.stackexchange.com/questions/27941/
            ];

            $process = new Process($command, env: [$varName => true]);
            $process->setOptions($config['options']);
            $process->setTty($config['tty']);
            $process->setPty($config['pty']);
            try {
                $sttyMode = shell_exec('stty -g');
                if ($config['alternate_screen']) {
                    echo shell_exec('tput smcup'); // echo "\e[7\e[?47h";
                }
                return $process->run();
            } finally {
                if ($config['alternate_screen']) {
                    echo shell_exec('tput rmcup'); // echo "\e[2J\e[?47l\e8";
                }
                $config['restore_stty'] && shell_exec(\sprintf('stty %s', $sttyMode));
            }
        };
    }

    /**
     * @return ArgumentMetadata[]
     */
    private static function meta(\ReflectionMethod|\ReflectionClass|callable $reflector): array
    {
        return new ArgumentMetadataFactory()->createArgumentMetadata(
            is_callable($reflector) ? $reflector : '',
            is_callable($reflector) ? null : $reflector
        );
    }

    private static function resolver(InputInterface $input, OutputInterface $output, ?Application $app = null): ArgumentResolver
    {
        $container = $app?->getKernel()?->getContainer();
        return new ArgumentResolver(
            argumentValueResolvers: [
                new ValueResolver(function (ArgumentMetadata $arg) use ($input, $output, $app, $container) {
                    if (!strpos($type = $arg->getType(), '\\')) {
                        return;
                    }
                    yield from match ($type) {
                        Input::class,
                        ArgvInput::class,
                        InputInterface::class => $input instanceof $type ? [$input] : [],
                        OutputInterface::class,
                        ConsoleOutputInterface::class,
                        ConsoleOutput::class => $output instanceof $type ? [$output] : [],
                        OutputStyle::class,
                        SymfonyStyle::class,
                        StyleInterface::class => [new SymfonyStyle($input, $output)],
                        Cursor::class => [new Cursor(
                            $output,
                            $input instanceof StreamableInputInterface ? $input->getStream() : null
                        )],
                        HelperSet::class => $app ? [$app->getHelperSet()] : [],
                        default => $container?->has($type) ? [$container->get($type)] : [],
                    };
                }),
                new ValueResolver(function (ArgumentMetadata $arg) use ($input) {
                    $name = $arg->getName();
                    if ($input->hasArgument($name)) {
                        yield $input->getArgument($name);
                    } else if ($input->hasOption($name)) {
                        yield $input->hasOption($name);
                    }
                }),
                ...ArgumentResolver::getDefaultArgumentValueResolvers(),
            ]
        );
    }
}
