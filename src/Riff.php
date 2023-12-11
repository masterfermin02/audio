<?php

namespace Masterfermin02\Audio;

class Riff extends Wave
{
    private string $chunkId;

    private float|int $chunkSize;

    private const HEIGHT_BITS = 8;

    public function __construct(
        public readonly File $file
    ) {
        $this->chunkId = $file->getChunkId();
        $this->chunkSize = $this->file->longCalc(Math::ZERO_MODE);
        if ($this->chunkId === "RIFF") {
            $formatLen = $this->chunkSize;
            $waveCompression = $this->file->shortCalc(Math::ZERO_MODE);
            $waveChannels = $this->file->shortCalc(Math::ZERO_MODE);
            $waveFramerate = $this->file->longCalc(Math::ZERO_MODE);
            $waveByterate = $this->file->longCalc(Math::ZERO_MODE);
            $waveLength = 0;
            $this->file->getShortChuckLineArray();
            $waveBits = $this->file->shortCalc(Math::ZERO_MODE);
            $this->file->readShortFormat($formatLen, Math::ONE_MODE);

            $chunkId = $this->file->getChunkLine();
            $chunkSize = $this->file->longCalc(Math::ZERO_MODE);
            if ($chunkId == "data")
            {
                $waveLength = (($chunkSize / $waveChannels) / ($waveBits/self::HEIGHT_BITS)) / $waveFramerate;
            } else {

                while ($chunkId != "data" && !$this->file->endOfFile())
                {
                    $j = 1;
                    while ($j <= $chunkSize && !$this->file->endOfFile())
                    {
                        $this->file->generateNullLine();
                        ++$j;
                    }

                    $chunkId = $this->file->getChunkLine();
                    $chunkSize = $this->file->longCalc(Math::ZERO_MODE);
                }

                if ($chunkId == "data")
                {
                    $waveLength = (($chunkSize / $waveChannels) / ($waveBits/self::HEIGHT_BITS)) / $waveFramerate;
                }
            }
            parent::__construct(
                fileName: $this->file->fileName,
                size: $this->file->getSize(),
                id: $this->chunkId,
                type: $this->file->getChunkType(),
                compression: $waveCompression,
                channels: $waveChannels,
                fameRate: $waveFramerate,
                byteRate: $waveByterate,
                bits: $waveBits,
                length: $waveLength,
            );
        } else {
            throw new \InvalidArgumentException('The file is not a RIFF file');
        }
    }
}
