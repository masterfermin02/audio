<?php

namespace Masterfermin02\Audio;

use Masterfermin02\Audio\ValueObjects\WaveSize;

/**
 *
 */
class File
{
    /**
     * @var false|resource
     */
    protected $file;

    protected string $chunkId;

    protected string $chunkType;

    protected WaveSize $size;

    public function __construct(
        public readonly string $fileName
    ) {
        $this->size = new WaveSize(filesize($fileName));
        $this->file = fopen ($fileName,"r");
    }

    public function generateInitialChunk(): void
    {
        $this->chunkId = $this->getChunkLine();
        $this->generateNullLine();
        $this->chunkType = $this->getChunkLine();
    }

    public function getChunkId(): string
    {
        return $this->chunkId;
    }

    public function getChunkLine(): string
    {
        return fgetc($this->file) . fgetc($this->file) . fgetc($this->file) . fgetc($this->file);
    }

    public function getChuckLineArray(): array
    {
        return [
            fgetc($this->file),
            fgetc($this->file),
            fgetc($this->file),
            fgetc($this->file),
        ];
    }

    public function getShortChuckLineArray(): array
    {
        return [
            fgetc($this->file),
            fgetc($this->file),
        ];
    }

    public function generateNullLine(): void
    {
        $this->getChunkLine();
    }

    public function generateShortNullLine(): void
    {
        $this->getShortChuckLineArray();
    }

    public function getChunkType(): string
    {
        return $this->chunkType;
    }

    public function getSize(): WaveSize
    {
        return $this->size;
    }

    public function readShortFormat(float|int $formatLen, int $mode): void
    {
        $read = 16;
        if ($read < $formatLen)
        {
            $extraBytes = $this->shortCalc($mode);
            $j = 0;
            while ($j < $extraBytes && !feof($this->file))
            {
                $this->generateNullLine();
                ++$j;
            }
        }
    }

    public function shortCalc(int $mode): float|int
    {
        $chunkArray = array_merge($this->getShortChuckLineArray(), [$mode]);
        return Math::shortCalc(...$chunkArray);
    }

    public function longCalc(int $mode): float|int
    {
        $chunkArray = array_merge($this->getChuckLineArray(), [$mode]);
        return Math::longCalc(...$chunkArray);
    }

    public function endOfFile(): bool
    {
        return feof($this->file);
    }

    public function fRead(int $length): string
    {
        return fread($this->file, $length);
    }

    public function fTell(): int
    {
        return ftell($this->file);
    }

    public function fSeek(int $offset, int $whence = SEEK_SET): int
    {
        return fseek($this->file, $offset, $whence);
    }

    public function fClose(): bool
    {
        return fclose($this->file);
    }

    public function rewind(): bool
    {
        return rewind($this->file);
    }

    public function __destroy(): void
    {
        $this->fClose();
    }
}
