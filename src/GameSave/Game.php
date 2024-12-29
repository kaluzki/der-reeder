<?php declare(strict_types=1);

namespace Kaluzki\DerReeder\GameSave;

use Psr\Http\Message\StreamInterface;

class Game
{
    public function __construct(
        readonly private StreamInterface $stream
    ) {}

    public function __destruct()
    {
        $this->stream->close();
    }

    public string $name {
        get => basename((string)$this->stream->getMetadata('uri'));
    }

    /**
     * @var iterable<string, City>
     */
    public iterable $cities {
        get => $this->readStrings(33507, 103, 100, City::class);
    }

    private function readStrings(
        int $offset,
        int $length,
        int $count,
        ?string $class = null,
        ?string $keyProperty = null
    ): iterable {
        $this->stream->seek($offset);
        for ($i = 0; $i < $count && !$this->stream->eof(); ++$i) {
            $data = $this->stream->read($length);
            $key = $i;
            if ($class) {
                $data = new $class($data);
                $keyProperty && $key = $data->$keyProperty;
            }
            yield $key => $data;
        }
    }

    public function bytes(int $offset, int $length): iterable
    {
        $this->stream->seek($offset);
        for ($i = 0; $i < abs($length); ++$i) {
            yield $this->stream->read(1);
        }
    }
}
