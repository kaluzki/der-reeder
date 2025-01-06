<?php

namespace Kaluzki\DerReeder\DependencyInjection\Attribute;

use Kaluzki\DerReeder\DependencyInjection\ValueResolver;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class AsCommand extends \Symfony\Component\Console\Attribute\AsCommand
{
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

    public static function createCode(callable $code, Command $command): callable
    {
        return function (InputInterface $input, OutputInterface $output) use ($code, $command) {
            $app = $command->getApplication();
            $container = $app instanceof Application ? $app->getKernel()->getContainer() : null;
            $resolver = new ArgumentResolver(
                argumentValueResolvers: [
                    new ValueResolver(function (ArgumentMetadata $arg) use ($container, $input, $output, $app) {
                        if (!strpos($type = $arg->getType(), '\\')) {
                            return;
                        }
                        yield from match ($type) {
                            InputInterface::class => [$input],
                            OutputInterface::class => [$output],
                            OutputStyle::class, SymfonyStyle::class, StyleInterface::class => [new SymfonyStyle($input, $output)],
                            HelperSet::class => [$app->getHelperSet()],
                            default => $container?->has($type) ? [$container?->get($type)] : [],
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
            $args = $resolver->getArguments(new Request(), $code);
            $return = $code(...$args);
            foreach (is_iterable($return) ? $return : [] as $line) {
                $output->writeln($line);
            }
            return is_int($return) ? $return : Command::SUCCESS;
        };
    }

}
