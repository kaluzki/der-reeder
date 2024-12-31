<?php

namespace Kaluzki\DerReeder\GameSave\Controller;

use Kaluzki\DerReeder\GameSave;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class MainController
{
    #[Route('/', methods: ['GET'])]
    public function renderGames(GameSave\Provider $games, Output $out): Response
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

    #[Route('/{game}/{action}', methods: ['GET'])]
    public function renderGame(
        #[ValueResolver(GameSave\Provider::class)] GameSave\Game $game,
        Output $out,
        string $action = 'show',
    ): Response {
        return $out->html(match ($action) {
            'stream' => $this->asStream($game->stream, $out),
            '' => $game->name,
            default => throw new NotFoundHttpException($action),
        }, "$out->title | $game->name | $action");
    }

    private function asStream(StreamInterface $stream, Output $out): iterable
    {
        yield <<<HTML
        <pre>{$out(json_encode($stream->getMetadata() + [
            'size' => $stream->getSize(),
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR))}</pre>
        HTML;
    }
}
