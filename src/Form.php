<?php

namespace Masterfermin02\Audio;

class Form extends Wave implements MetaData
{
    public function __construct(
        public readonly File $file,
        public readonly PrintBasicInfo $printBasicInfo,
    ) {
        $chunkId = $this->file->getChunkLine();
        $chunkSize = $this->file->longCalc(Math::ZERO_MODE);
        if ($chunkId == "COMM")
        {
            $formatLen = $chunkSize;
            $waveChannels = $this->file->shortCalc(Math::ONE_MODE);
            $this->file->generateNullLine();
            $waveBits = $this->file->shortCalc(Math::ONE_MODE);
            $file->generateShortNullLine();
            $waveFramerate = $this->file->shortCalc(Math::ONE_MODE);
            return parent::__construct(
                $this->file->fileName,
                $this->file->getSize(),
                $chunkId,
                'FORM',
                0,
                $waveChannels,
                $waveFramerate,
                0,
                $waveBits,
                $formatLen,
            );
        } else {
            throw new \InvalidArgumentException('The file is not a wav file');
        }
    }

    public function printInfo(): void
    {
        $this->printBasicInfo->printInfo($this);
    }

    public function visualize(): void
    {
        // TODO: Implement visualize() method.
    }
}
