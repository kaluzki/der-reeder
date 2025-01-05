<?php

namespace Kaluzki\DerReeder\DependencyInjection\Attribute;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class AsCommand extends \Symfony\Component\Console\Attribute\AsCommand
{
    public static function passHandler(
        self $attr,
        ContainerBuilder $container,
        string $id,
    ): void {
        $container->register(".$id." . $attr::class, Command::class)
            ->addTag('console.command', [
                'command' => implode('|', [$attr->name]),
                'description' => $attr->description ?? ' '
            ])->addMethodCall('setCode', [new Reference($id)]);
    }
}
