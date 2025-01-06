<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Kaluzki\DerReeder;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\Finder\Finder;

return function(ContainerConfigurator $container): void {

    $container->parameters()
        ->set('env(REEDER_GAMESAVE_DIR)', 'resources/GAMESAVE/')
        ->set('env(REEDER_ASSETS)', DerReeder\GameSave\Controller\Output::BS)
        ->set('env(REEDER_ASSETS_CDN)', DerReeder\GameSave\Controller\Output::BS_CDN)
    ;

    $container->services()->defaults()->autowire()->autoconfigure()

        ->set(DerReeder\GameSave\Controller\Output::class)->args([
            env('REEDER_ASSETS'),
            env('REEDER_ASSETS_CDN'),
            '$title' => 'Der Reeder',
            '$template' => <<<HTML
            <div class="container-fluid"> 
            %s
            </div>
            HTML,
        ])

        ->set(DerReeder\GameSave\Controller\MainController::class)->tag('controller.service_arguments')

        ->set(Psr17Factory::class)
            ->alias(StreamFactoryInterface::class, Psr17Factory::class)

        ->set('reeder.gamesave.provider.files', Finder::class)
            ->call('files')
            ->call('in', [env('REEDER_GAMESAVE_DIR')])
            ->call('name', ['*.SVE'])
            ->call('sortByName')

        ->set(DerReeder\GameSave\Provider::class)->args([service('reeder.gamesave.provider.files')])

        ->set(DerReeder\GameSave\Command::class)
    ;
};