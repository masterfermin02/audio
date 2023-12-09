<?php

namespace Masterfermin02\Audio;

use InvalidArgumentException;
use Masterfermin02\Audio\ValueObjects\Id3v2;

class Audio
{
    public ?Id3v2 $id3v2;

    public Wave|Mp3|Ogg $wave;
    /**
     * @var bool
     */
    public bool $valid;
    public $vorbisComment;
    public $waveId;

    public $waveType;

    public $waveCompression;

    public $waveChannels;

    public $waveFramerate;

    public $waveByterate;

    public $waveBits;

    public $waveSize;

    public $waveFilename;

    public $waveLength;

    public $id3Tag;

    public $id3Title;

    public $id3Artist;

    public $id3Album;

    public $id3Year;

    public $id3Comment;

    public $id3Genre;

    public $id3v2info;

    public $visualGraphColor;
     // HTML-Style: "#rrggbb"
    public $visualBackgroundColor;

    public $visualGridColor;

    public $visualBorderColor;

    public $visualGrid;
     // true/false
    public $visualBorder;
     // true/false
    public $visualWidth;
     // width in pixel
    public $visualHeight;
     // height in pixel
    public $visualGraphMode;
     // 0|1
    public $visualFileformat; // "jpeg","png", everything & else default = "png"

    public $bytePrv;

    public array $info = [];

    private string $imageBaseDir = './';

    public function __construct(
        public readonly Mp3Info $mp3Info = new Mp3Info(),
        public readonly WaveFactory $waveFactory = new WaveFactory(),
    ) {
    }

    public static function create(): self
    {
        return new static();
    }

    /**
     * // ************************************************************************
     * // getCompression delivers a string which identifies the compression-mode
     * // of the AudioFile-Object
     * // ************************************************************************
     * @param $id
     * @return mixed|string
     */

    public function getCompression($id): string
    {
        if ($this->waveId != "MPEG" && $this->waveId !="OGG") {
            $append = sprintf('(%s)', $id);
            return match ($id) {
                0 => 'unknown ' . $append,
                1 => 'pcm/uncompressed ' . $append,
                2 => 'microsoft adpcm ' . $append,
                6 => 'itu g.711 a-law ' . $append,
                7 => 'itu g.711 u-law ' . $append,
                17 => 'ima adpcm ' . $append,
                20 => 'itu g.723 adpcm (yamaha) ' . $append,
                49 => 'gsm 6.10 ' . $append,
                64 => 'itu g.721 adpcm ' . $append,
                80 => 'mpeg ' . $append,
                65536 => 'experimental ' . $append,
                default => 'not defined ' . $append,
            };
        }

        return ($id);
    }

    public function setImageBaseDir(string $dir): self
    {
        $this->imageBaseDir = $dir;

        return $this;
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
    public function getVisualization(string $filename): string
    {
        $output = $this->imageBaseDir . substr($filename,0,strlen($filename)-4) . ".png";
        $width=$this->visualWidth;
        $height=$this->visualHeight;
        $height_channel = $height / $this->waveChannels;
        if ($this->hasVisualization())
        {
            $file = fopen ($this->waveFilename,"r");

            // read the first 12 bytes (RIFF- & WAVE-chunk)
            for ($i=0;$i<12;++$i)
            {
                $null = fgetc ($file);
            }

            // Read the next chunk-id, supposed to be "fmt "

            $chunk_id_3 = fgetc($file) . fgetc($file) . fgetc($file) . fgetc($file);
            if ($chunk_id_3 == "fmt ")
            {
                $chunk_size_3 = $this->longCalc (fgetc($file) , fgetc($file) , fgetc($file) , fgetc($file),0);
                for ($i=0;$i<$chunk_size_3;++$i)
                {
                    $null = fgetc($file);
                }

                // Read the next chunk-id, supposed to be "data"
                $chunk_id_4 = "";
                while ($chunk_id_4 != "data" && !feof($file))
                {
                    $chunk_id_4 = fgetc($file) . fgetc($file) . fgetc($file) . fgetc($file);
                    if ($chunk_id_4 != "data")
                    {
                        $chunk_size_4 = $this->longCalc (fgetc($file) , fgetc($file) , fgetc($file) , fgetc($file),0);
                        for ($i=0;$i<$chunk_size_4;++$i)
                        {
                            $null = fgetc($file);
                        }
                    }
                }

                if ($chunk_id_4 == "data")
                {
                    $chunk_size_4 = $this->longCalc (fgetc($file) , fgetc($file) , fgetc($file) , fgetc($file),0);
                    $visualData = [];
                    $bytes_per_frame = ($this->waveBits/8)*($this->waveChannels);
                    $bytes_per_channel = ($this->waveBits/8);
                    $frames = $chunk_size_4 / $bytes_per_frame;
                    $visual_frames = ceil($frames / $width);
                    $frame_index = 1;
                    $data_index = 1;

                    // revised code -- computing bytes per pixel allows quick processing of large (>10MB) wavs by fseek()ing past unused data
                    $bytes_per_pixel= floor($chunk_size_4/$width);
                    $currentindex= 0;
                    while (!feof($file) && $currentindex < $chunk_size_4)
                    {
                        $loopindex= 0;
                        for ($j=0; $j<$this->waveChannels; ++$j)
                        {
                            $bytes = [];
                            for ($i=0;$i<$bytes_per_channel;++$i)
                            {
                                $bytes[$i] = fgetc($file);
                                ++$loopindex;
                            }

                            switch ($bytes_per_channel)
                            {
                                case 1: $visualData[$j][$data_index]= $this->shortCalc($bytes[0],$bytes[1],0);
                                    break;
                                case 2: $f=128;
                                if ((ord($bytes[1])&128) !== 0) {
                                    $f = 0;
                                }

                                    $x=chr((ord($bytes[1])&127) + $f);
                                    $visualData[$j][$data_index]= floor($this->shortCalc($bytes[0],$x,0)/256);
                                    break;
                            }

                            if (($j+1) == $this->waveChannels)
                            {
                                ++$data_index;
                            }
                        }

                        $currentindex+= ( $bytes_per_pixel - $loopindex );
                        fseek($file, $bytes_per_pixel, SEEK_CUR);
                    }

                    //$im = @ImageCreate ($width, (256*$this->wave_channels)+1) or die ("Cannot Initialize new GD image stream!");
                    ($im = @ImageCreate ($width, $height)) || die ("Cannot Initialize new GD image stream!");
                    $background_color = ImageColorAllocate ($im, hexdec(substr((string) $this->visualBackgroundColor,1,2)),hexdec(substr((string) $this->visualBackgroundColor,3,2)),hexdec(substr((string) $this->visualBackgroundColor,5,2)));
                    $cBlack = ImageColorAllocate ($im, hexdec(substr((string) $this->visualBackgroundColor,1,2)),hexdec(substr((string) $this->visualBackgroundColor,3,2)),hexdec(substr((string) $this->visualBackgroundColor,5,2)));
                    $cGreen = ImageColorAllocate ($im, hexdec(substr((string) $this->visualGraphColor,1,2)),hexdec(substr((string) $this->visualGraphColor,3,2)),hexdec(substr((string) $this->visualGraphColor,5,2)));
                    $cRed = ImageColorAllocate ($im, hexdec(substr((string) $this->visualBorderColor,1,2)),hexdec(substr((string) $this->visualBorderColor,3,2)),hexdec(substr((string) $this->visualBorderColor,5,2)));
                    $cBlue = ImageColorAllocate ($im, hexdec(substr((string) $this->visualGridColor,1,2)),hexdec(substr((string) $this->visualGridColor,3,2)),hexdec(substr((string) $this->visualGridColor,5,2)));
                    if ($this->visualBorder)
                    {
                        ImageRectangle ($im,0,0,($width-1),($height-1),$cRed);
                        for ($i=0; $i<=$this->waveChannels; ++$i)
                        {
                            ImageLine ($im,1,($i*($height_channel/2))+($height_channel/2),$width,($i*($height_channel/2))+($height_channel/2),$cRed);
                        }
                    }

                    if ($this->visualGrid)
                    {
                        for ($i=1;$i<=($width/100*2);++$i)
                        {
                            ImageLine ($im,$i*50,0,$i*50,(256*$this->waveChannels),$cBlue);
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
                                ImageLine ($im,$last_x,($last_y+($j*$height_channel)),$i,($val+($j*$height_channel)),$cGreen);
                            } else {
                                ImageLine ($im,$i,(($height_channel/2)+($j*$height_channel)),$i,($val+($j*$height_channel)),$cGreen);
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

            fclose ($file);
        }

        return $output;
    }

    private function hasVisualization(): bool
    {
        return $this->waveFilename != ""
            && $this->waveId == "RIFF"
            && $this->waveType == "WAVE"
            && ($this->waveChannels>=1 && $this->waveChannels<=2)
            && $this->waveBits%8==0;
    }

    // ************************************************************************
    // getSampleInfo extracts the attributes of the AudioFile-Object
    // ************************************************************************

    public function getSampleInfo(): bool
    {
        try {
            $this->wave = $this->waveFactory->setFileName($this->waveFilename)
                ->build();
        } catch (InvalidArgumentException) {
            return false;
        }

        return true;
    }

    // ************************************************************************
    // printSampleInfo prints the attributes of the AudioFile-Object
    // ************************************************************************

    public function printSampleInfo(): void
    {
        print "<table width=100% border=1>";
        print sprintf('<tr><td align=right>filename</td> <td>&nbsp;%s</td></tr>', $this->wave->fileName);
        print sprintf('<tr><td align=right>id</td> <td>&nbsp;%s</td></tr>', $this->wave->id);
        print sprintf('<tr><td align=right>type</td> <td>&nbsp;%s</td></tr>', $this->wave->type);
        print sprintf('<tr><td align=right>size</td> <td>&nbsp;%s</td></tr>', $this->wave->size->value());
        print "<tr><td align=right>compression</td> <td>&nbsp;".$this->getCompression ($this->wave->compression)."</td></tr>";
        print sprintf('<tr><td align=right>channels</td> <td>&nbsp;%s</td></tr>', $this->wave->channels);
        print sprintf('<tr><td align=right>framerate</td> <td>&nbsp;%s</td></tr>', $this->wave->fameRate);
        print sprintf('<tr><td align=right>byterate</td> <td>&nbsp;%s</td></tr>', $this->wave->byteRate);
        print sprintf('<tr><td align=right>bits</td> <td>&nbsp;%s</td></tr>', $this->wave->bits);
        print "<tr><td align=right>length</td> <td>&nbsp;".number_format ($this->wave->length,"2")." sec.<br>&nbsp;".date("i:s", mktime(0,0,round($this->wave->length)))."</td></tr>";

        // ID3V1
        if ($this->wave->id3Tag)
        {
            print "<tr><td align=right>id3v1-tags</td><td>";
            print "<table width=100% border=1>";
            print sprintf('<tr><td width=70 align=right>title</td><td>&nbsp;%s</td></tr>', $this->id3Title);
            print sprintf('<tr><td align=right>artist</td><td>&nbsp;%s</td></tr>', $this->id3Artist);
            print sprintf('<tr><td align=right>album</td><td>&nbsp;%s</td></tr>', $this->id3Album);
            print sprintf('<tr><td align=right>year</td><td>&nbsp;%s</td></tr>', $this->id3Year);
            print sprintf('<tr><td align=right>comment</td><td>&nbsp;%s</td></tr>', $this->id3Comment);
            print sprintf('<tr><td align=right>genre</td><td>&nbsp;%s</td></tr>', $this->id3Genre);
            print "</table>";
            print "</td></tr>";
        } else {
            print "<tr><td align=right>id3v1-tags</td><td>Not found</td></tr>";
        }

        if ($this->wave->id3v2)
        {
            print "<tr><td align=right>id3v2-tags</td><td>";
            print "<table width=100% border=1>";
            print "<tr><td width=70 align=right>title</td><td>&nbsp;".$this->id3v2->TIT2."</td></tr>";
            print "<tr><td align=right>artist</td><td>&nbsp;".$this->id3v2->TPE1."</td></tr>";
            print "<tr><td align=right>original artist</td><td>&nbsp;".$this->id3v2->TOPE."</td></tr>";
            print "<tr><td align=right>album</td><td>&nbsp;".$this->id3v2->TALB."</td></tr>";
            print "<tr><td align=right>year</td><td>&nbsp;".$this->id3v2->TYER."</td></tr>";
            print "<tr><td align=right>comment</td><td>&nbsp;".$this->id3v2->COMM."</td></tr>";
            print "<tr><td align=right>composer</td><td>&nbsp;".$this->id3v2->TCOM."</td></tr>";
            print "<tr><td align=right>genre</td><td>&nbsp;".$this->id3v2->TCON."</td></tr>";
            print "<tr><td align=right>encoder</td><td>&nbsp;".$this->id3v2->TENC."</td></tr>";
            print "<tr><td align=right>website</td><td>&nbsp;".$this->id3v2->WXXX."</td></tr>";
            print "</table>";
            print "</td></tr>";
        }
        else
        {
            print "<tr><td align=right>id3v2 tags</td><td>Not found</td></tr>";
        }

        // VORBIS
        if ($this->wave->id == "OGG")
        {
            print "<tr><td align=right>ogg-tags</td><td>";
            print "<table width=100% border=1>";
            print "<tr><td width=70 align=right>title</td><td>&nbsp;".$this->vorbisComment->TITLE."</td></tr>";
            print "<tr><td align=right>artist</td><td>&nbsp;".$this->vorbisComment->ARTIST."</td></tr>";
            print "<tr><td align=right>album</td><td>&nbsp;".$this->vorbisComment->ALBUM."</td></tr>";
            print "<tr><td align=right>date</td><td>&nbsp;".$this->vorbisComment->DATE."</td></tr>";
            print "<tr><td align=right>genre</td><td>&nbsp;".$this->vorbisComment->GENRE."</td></tr>";
            print "<tr><td align=right>comment</td><td>&nbsp;".$this->vorbisComment->COMMENT."</td></tr>";
            print "</table>";
            print "</td></tr>";
        } else {
            print "<tr><td align=right>ogg vorbis info</td><td>Not found</td></tr>";
        }

        print "</table>";
    }

    // ************************************************************************
    // loadFile initializes the AudioFile-Object
    // ************************************************************************

    public function loadFile($loadFilename): void
    {
        $this->waveFilename = $loadFilename;
        $this->getSampleInfo();
        $this->visualGraphColor = "#18F3AD";
        $this->visualBackgroundColor = "#000000";
        $this->visualGridColor = "#002C4A";
        $this->visualBorderColor = "#A52421";
        $this->visualGrid = true;
        $this->visualBorder = true;
        $this->visualWidth = 600;
        $this->visualHeight = 512;
        $this->visualGraphMode = 1;
        $this->visualFileformat = "png";
    }
}
