<?php declare(strict_types=1);

namespace Kaluzki\DerReeder\GameSave;

use Psr\Http\Message\StreamInterface;

class Entity
{
    public function __construct(
        readonly private StreamInterface $stream
    ) {}

    public function __destruct()
    {
        $this->stream->close();
    }

    public string $name {
        get => basename((string)$this->stream);
    }
}
