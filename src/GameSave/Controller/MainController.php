<?php

namespace Kaluzki\DerReeder\GameSave\Controller;

use Kaluzki\DerReeder\GameSave;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class MainController
{
    #[Route('/', methods: ['GET'])]
    public function index(GameSave\Provider $games, Output $out): Response
    {
        return $out->html(<<<HTML
        <div class="container">
            <div class="row row-cols-3 g-3">
                {$out($games, fn (GameSave\Game $game, string $name) => yield <<<HTML
                <a class="col text-decoration-none" href="/$game->name/"> 
                    $game->name
                    <div class="fs-6 text-dark">$name</div>
                </a>
                HTML)}
            </div>
        </div>
        HTML);
    }

    #[Route('/{name}', methods: ['GET'])]
    public function game(GameSave\Provider $games, Output $out, string $name): Response
    {
        return match ($game = $games->get($name)) {
            null => throw new NotFoundHttpException($name),
            default => $out->html($game->name)
        };
    }
}
