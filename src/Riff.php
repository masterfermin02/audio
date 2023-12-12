<?php

namespace Masterfermin02\Audio;

class Riff extends Wave implements MetaData
{
    private string $chunkId;

    private float|int $chunkSize;

    private const HEIGHT_BITS = 8;
    private string $visualGraphColor = "#18F3AD";
    private string $visualBackgroundColor = "#000000";
    private string $visualGridColor = "#002C4A";
    private string $visualBorderColor = "#A52421";
    private bool $visualGrid = true;
    private bool $visualBorder = true;
    private int $visualWidth = 600;
    private int $visualHeight = 512;
    private int $visualGraphMode = 1;
    private string $visualFileformat = "png";

    public function __construct(
        public readonly File $file,
        public readonly PrintBasicInfo $printBasicInfo,
        public readonly string $imageBaseDir,
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

    public function printInfo(): void
    {
        $this->printBasicInfo->printInfo($this);
    }

    /**
    // ************************************************************************
    // getVisualization creates a graphical visualization of the audio-sample
    // (works ONLY * for uncompressed waves!
    // * files with 1 or 2 channels
    // * 8/16/24/32 bit sample-resolution )
    // ************************************************************************
     *
     * */
    public function getVisualization(): string
    {
        $output = $this->imageBaseDir . substr($this->fileName,0,strlen($this->fileName)-4) . ".png";
        $width = $this->visualWidth;
        $height = $this->visualHeight;
        $height_channel = $this->channels > 0 ? $height / $this->channels : 1;
        if ($this->hasVisualization())
        {
            $file = new File($this->fileName);

            // read the first 12 bytes (RIFF- & WAVE-chunk)
            for ($i=0;$i<12;++$i)
            {
                $file->generateNullLine();
            }

            // Read the next chunk-id, supposed to be "fmt "

            $chunk_id_3 = $file->getChunkLine();
            if ($chunk_id_3 == "fmt ")
            {
                $chunk_size_3 = $file->longCalc(Math::ZERO_MODE);
                for ($i=0;$i<$chunk_size_3;++$i)
                {
                    $file->generateNullLine();
                }

                // Read the next chunk-id, supposed to be "data"
                $chunk_id_4 = "";
                while ($chunk_id_4 != "data" && !$file->endOfFile())
                {
                    $chunk_id_4 = $file->getChunkLine();
                    if ($chunk_id_4 != "data")
                    {
                        $chunk_size_4 = $file->longCalc(Math::ZERO_MODE);
                        for ($i=0;$i<$chunk_size_4;++$i)
                        {
                            $file->generateNullLine();
                        }
                    }
                }

                if ($chunk_id_4 == "data")
                {
                    $chunk_size_4 = $file->longCalc(Math::ZERO_MODE);
                    $visualData = [];
                    $bytes_per_frame = ($this->bits/8)*($this->channels);
                    $bytes_per_channel = ($this->bits/8);
                    $frames = $chunk_size_4 / $bytes_per_frame;
                    $visual_frames = ceil($frames / $width);
                    $frame_index = 1;
                    $data_index = 1;

                    // revised code -- computing bytes per pixel allows quick processing of large (>10MB) wavs by fseek()ing past unused data
                    $bytes_per_pixel= floor($chunk_size_4/$width);
                    $currentindex= 0;
                    while (!$file->endOfFile() && $currentindex < $chunk_size_4)
                    {
                        $loopindex= 0;
                        for ($j=0; $j<$this->channels; ++$j)
                        {
                            $bytes = [];
                            for ($i=0;$i<$bytes_per_channel;++$i)
                            {
                                $bytes[$i] = $file->fGetC();
                                ++$loopindex;
                            }

                            switch ($bytes_per_channel)
                            {
                                case 1: $visualData[$j][$data_index]= Math::shortCalc($bytes[0],$bytes[1],0);
                                    break;
                                case 2: $f=128;
                                    if ((ord($bytes[1])&128) !== 0) {
                                        $f = 0;
                                    }

                                    $x=chr((ord($bytes[1])&127) + $f);
                                    $visualData[$j][$data_index]= floor(Math::shortCalc($bytes[0],$x,0)/256);
                                    break;
                            }

                            if (($j+1) == $this->channels)
                            {
                                ++$data_index;
                            }
                        }

                        $currentindex+= ( $bytes_per_pixel - $loopindex );
                        $file->fSeek($bytes_per_pixel - $loopindex, SEEK_CUR);
                    }

                    //$im = @ImageCreate ($width, (256*$this->wave_channels)+1) or die ("Cannot Initialize new GD image stream!");
                    ($im = @ImageCreate($width, $height)) || die ("Cannot Initialize new GD image stream!");
                    $background_color = ImageColorAllocate($im, hexdec(substr((string) $this->visualBackgroundColor,1,2)),hexdec(substr((string) $this->visualBackgroundColor,3,2)),hexdec(substr((string) $this->visualBackgroundColor,5,2)));
                    $cBlack = ImageColorAllocate($im, hexdec(substr((string) $this->visualBackgroundColor,1,2)),hexdec(substr((string) $this->visualBackgroundColor,3,2)),hexdec(substr((string) $this->visualBackgroundColor,5,2)));
                    $cGreen = ImageColorAllocate($im, hexdec(substr((string) $this->visualGraphColor,1,2)),hexdec(substr((string) $this->visualGraphColor,3,2)),hexdec(substr((string) $this->visualGraphColor,5,2)));
                    $cRed = ImageColorAllocate($im, hexdec(substr((string) $this->visualBorderColor,1,2)),hexdec(substr((string) $this->visualBorderColor,3,2)),hexdec(substr((string) $this->visualBorderColor,5,2)));
                    $cBlue = ImageColorAllocate($im, hexdec(substr((string) $this->visualGridColor,1,2)),hexdec(substr((string) $this->visualGridColor,3,2)),hexdec(substr((string) $this->visualGridColor,5,2)));
                    if ($this->visualBorder)
                    {
                        ImageRectangle($im,0,0,($width-1),($height-1),$cRed);
                        for ($i=0; $i<=$this->channels; ++$i)
                        {
                            ImageLine($im,1,($i*($height_channel/2))+($height_channel/2),$width,($i*($height_channel/2))+($height_channel/2),$cRed);
                        }
                    }

                    if ($this->visualGrid)
                    {
                        for ($i=1;$i<=($width/100*2);++$i)
                        {
                            ImageLine($im,$i*50,0,$i*50,(256*$this->channels),$cBlue);
                        }
                    }
                    // this for-loop draws a graph for every channel
                    $counter = count($visualData);

                    // this for-loop draws a graph for every channel

                    for ($j=0;$j<$counter;++$j)
                    {
                        $last_x = 1;
                        $last_y = $height_channel / 2;
                        // this for-loop draws the graphs itself
                        $counter = count($visualData[$j]);

                        // this for-loop draws the graphs itself

                        for ($i=1;$i<$counter;++$i)
                        {
                            $faktor = 128 / ($height_channel / 2);
                            $val = $visualData[$j][$i] / $faktor;
                            if ($this->visualGraphMode == 0)
                            {
                                ImageLine($im,$last_x,($last_y+($j*$height_channel)),$i,($val+($j*$height_channel)),$cGreen);
                            } else {
                                ImageLine($im,$i,(($height_channel/2)+($j*$height_channel)),$i,($val+($j*$height_channel)),$cGreen);
                            }

                            $last_x = $i;
                            $last_y = $val;
                        }
                    }

                    // change this to generate JPG or direct output to browser
                    if (strtolower((string) $this->visualFileformat) == "jpeg")
                    {
                        ImageJpeg($im,$output);
                    } else {
                        ImagePng($im,$output);
                    }
                }
            }

            $file->fClose();
        }

        return $output;
    }

    private function hasVisualization(): bool
    {
        return $this->fileName != ""
            && $this->id == "RIFF"
            && $this->type == "WAVE"
            && ($this->channels >= 1 && $this->channels <= 2)
            && $this->bits % 8 == 0;
    }

    public function visualize(): void
    {
        $imageSrc = $this->getVisualization();
        print "<img src='./$imageSrc' alt='image generated.' />";
    }
}
