<?php

namespace Masterfermin02\Audio;

use Masterfermin02\Audio\ValueObjects\WaveSize;

class WaveFactory
{
    private string $fileName;
    private WaveSize $size;
    private string $id;
    private string $type;
    private string $compression;
    private string $channels;
    private string $fameRate;
    private string $byteRate;
    private string $bits;
    private string $length;

    private File $file;

    public function __construct(){}

    public function create(): self
    {
        return new static();
    }

    public function build(): Wave|Mp3|Ogg
    {
        $this->file = new File($this->fileName);

        if ($this->isMp3()) {
            return new Mp3(
                $this->file,
                new Mp3Info(),
                new PrintBasicInfo()
            );
        }

        if ($this->isOgg()) {
            return new Ogg(
                $this->file,
                new PrintBasicInfo()
            );
        }

        $this->file->generateInitialChunk();
        $this->id = $this->file->getChunkId();
        $this->type = $this->file->getChunkType();

        if ($this->isPK()) {
            return new Zip(
                $this->fileName,
                $this->size,
                $this->id,
                $this->type,
                $this->compression,
                $this->channels,
                $this->fameRate,
                $this->byteRate,
                $this->bits,
                $this->length,
            );
        }

        if ($this->isRiff()) {
            return new Riff(
                $this->file,
                new PrintBasicInfo(),
                './'
            );
        }

        if ($this->isForm()) {
            return new Form(
                $this->file,
                new PrintBasicInfo(),
            );
        }

        return new Wave(
            $this->fileName,
            $this->size,
            $this->id,
            $this->type,
            $this->compression,
            $this->channels,
            $this->fameRate,
            $this->byteRate,
            $this->bits,
            $this->length,
        );
    }

    private function isMp3(): bool
    {
        return str_ends_with($this->fileName, "mp3");
    }

    private function isOgg(): bool
    {
        return str_ends_with($this->fileName, "ogg");
    }

    private function isPK(): bool
    {
        return str_starts_with($this->id, "PK");
    }

    private function isRiff(): bool
    {
        return $this->id == "RIFF" && $this->type == "WAVE";
    }

    private function isForm(): bool
    {
        return $this->id == "FORM" && $this->type == "AIFF";
    }

    public function setFileName(string $fileName): self
    {
        $this->fileName = $fileName;
        return $this;
    }

    public function setSize(WaveSize $size): self
    {
        $this->size = $size;
        return $this;
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function setCompression(string $compression): self
    {
        $this->compression = $compression;
        return $this;
    }

    public function setChannels(string $channels): self
    {
        $this->channels = $channels;
        return $this;
    }

    public function setFameRate(string $fameRate): self
    {
        $this->fameRate = $fameRate;
        return $this;
    }

    public function setByteRate(string $byteRate): self
    {
        $this->byteRate = $byteRate;
        return $this;
    }

    public function setBits(string $bits): self
    {
        $this->bits = $bits;
        return $this;
    }

    public function setLength(string $length): self
    {
        $this->length = $length;
        return $this;
    }

    public function setFile(File $file): self
    {
        $this->file = $file;
        return $this;
    }
}
