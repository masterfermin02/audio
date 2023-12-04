<?php

namespace Masterfermin02\Audio;

use Masterfermin02\Audio\ValueObjects\Id3v2;

class Audio
{
    public ?Id3v2 $id3v2;
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

    public function __construct(
        public readonly Mp3Info $mp3Info = new Mp3Info(),
    ) {
    }

    // ************************************************************************
    // mp3info extracts the attributes of mp3-files
    // (code contributed by reto gassmann (gassi@gassi.cx)
    // ************************************************************************

    public function mp3info(): void
    {
        //id3v2 check----------------------------
        $header = 0;
        $v1tag = 0;
        $fp = fopen($this->waveFilename,"r");
        $tmp = fread($fp,3);
        if($tmp == "ID3")
        {
            // id3v2 tag is present
            $this->getId3v2($fp);

            // getId3v2 will position pointer at end of header
            $header= ftell($fp);

        } else {
            fseek ($fp,0);
            $this->id3v2 = null;
        }

        for ($x=0;$x<4;++$x)
        {
            $byte[$x] = ord(fread($fp,1));
        }

        fseek ($fp, -128 ,SEEK_END);
        $TAG = fread($fp,128);
        fclose($fp);

        //id tag?-------------------------------

        if(str_starts_with($TAG, "TAG"))
        {
            $v1tag = 128;
            $this->info["mpeg_id3v1_tag"]["title"] = rtrim(substr($TAG,3,30));
            $this->info["mpeg_id3v1_tag"]["artist"] = rtrim(substr($TAG,33,30));
            $this->info["mpeg_id3v1_tag"]["album"] = rtrim(substr($TAG,63,30));
            $this->info["mpeg_id3v1_tag"]["year"] = rtrim(substr($TAG,93,4));
            $this->info["mpeg_id3v1_tag"]["comment"] = rtrim(substr($TAG,97,30));
            $this->info["mpeg_id3v1_tag"]["genre"] = "";
            $tmp = ord(substr($TAG,127,1));
            if($tmp < count($this->mp3Info->genre))
            {
                $this->info["mpeg_id3v1_tag"]["genre"] = $this->mp3Info->genre[$tmp];
            }
        } else {
            $this->info["mpeg_id3v1_tag"] = false;
        }

        //version-------------------------------

        $tmp = $byte[1] & 24;
        $tmp >>= 3;
        $byte_v = $this->mp3Info->versionBitrate[$tmp];
        $byte_vs = $this->mp3Info->versionSampling[$tmp];
        $this->info["mpeg_version"] = $this->mp3Info->version[$tmp];

        //layer---------------------------------

        $tmp = $byte[1] & 6;
        $tmp >>= 1;
        $byte_l = $this->mp3Info->layerBitrate[$tmp];
        $byte_len = $this->mp3Info->layerLength[$tmp];
        $this->info["mpeg_layer"] = $this->mp3Info->layer[$tmp];

        //bitrate-------------------------------

        $tmp = $byte[2] & 240;
        $tmp >>= 4;
        $this->info["mpeg_bitrate"] = $this->mp3Info->byteRate[$byte_v][$byte_l][$tmp];

        //samplingrate--------------------------

        $tmp = $byte[2] & 12;
        $tmp >>= 2;
        $this->info["mpeg_sampling_rate"] = $this->mp3Info->samplingRate[$byte_vs][$tmp];

        //protection----------------------------

        $tmp = $byte[1] & 1;
        $this->info["mpeg_protection"] = $this->mp3Info->protection[$tmp];

        //paddingbit----------------------------

        $tmp = $byte[2] & 2;
        $tmp >>= 1;

        $byte_pad = $tmp;

        //privatebit----------------------------

        $tmp = $byte[2] & 1;
        $this->bytePrv = $tmp;

        //channel_mode--------------------------

        $tmp = $byte[3] & 192;
        $tmp >>= 6;
        $this->info["mpeg_channel_mode"] = $this->mp3Info->channelMode[$tmp];

        //copyright-----------------------------

        $tmp = $byte[3] & 8;
        $tmp >>= 3;
        $this->info["mpeg_copyright"] = $this->mp3Info->copyright[$tmp];

        //original------------------------------

        $tmp = $byte[3] & 4;
        $tmp >>= 2;
        $this->info["mpeg_original"] = $this->mp3Info->original[$tmp];

        //emphasis------------------------------

        $tmp = $byte[3] & 3;
        $this->info["mpeg_emphasis"] = $this->mp3Info->emphasis[$tmp];

        //framelenght---------------------------

        if ($this->info["mpeg_bitrate"] == 'free' || $this->info["mpeg_bitrate"] == 'bad' || !$this->info["mpeg_bitrate"] || !$this->info["mpeg_sampling_rate"]) {
            $this->info["mpeg_framelength"] = 0;
        } elseif ($byte_len == 0) {
            $rate_tmp = $this->info["mpeg_bitrate"] * 1000;
            $this->info["mpeg_framelength"] = (12 * $rate_tmp / $this->info["mpeg_sampling_rate"] + $byte_pad) * 4 ;
        } elseif($byte_len == 1) {
            $rate_tmp = $this->info["mpeg_bitrate"] * 1000;
            $this->info["mpeg_framelength"] = 144 * $rate_tmp / $this->info["mpeg_sampling_rate"] + $byte_pad;
        }

        //duration------------------------------

        $tmp = filesize($this->waveFilename);
        $tmp = $tmp - $header - 4 - $v1tag;

        $this->info["mpeg_frames"]="";
        $this->info["mpeg_playtime"]="";
        if(!$this->info["mpeg_bitrate"] || $this->info["mpeg_bitrate"] == 'bad' || !$this->info["mpeg_sampling_rate"])
        {
            $info["mpeg_playtime"] = -1;
        } elseif($this->info["mpeg_bitrate"] == 'free')
        {
            $info["mpeg_playtime"] = -1;
        } else {
            $tmp2 = ((8 * $tmp) / 1000) / $this->info["mpeg_bitrate"];
            $info["mpeg_frames"] = floor($tmp/$this->info["mpeg_framelength"]);
            $tmp *= 8;
            if ($rate_tmp != 0)
            {
                $this->info["mpeg_playtime"] = $tmp/$rate_tmp;
            } else {
                $this->info["mpeg_playtime"] = $tmp2;
            }
        }

        // transfer the extracted data into classAudioFile-structure

        $this->waveId = "MPEG";
        $this->waveType = $this->info["mpeg_version"];
        $this->waveCompression = $this->info["mpeg_layer"];
        $this->waveChannels = $this->info["mpeg_channel_mode"] ?? '';
        $this->waveFramerate = $this->info["mpeg_sampling_rate"];
        $this->waveByterate = $this->info["mpeg_bitrate"] . " Kbit/sec";
        $this->waveBits = "n/a";
        $this->waveSize = filesize($this->waveFilename);
        $this->waveLength = $this->info["mpeg_playtime"];

        // pick up length from id3v2 tag if necessary and available
        if ($this->waveLength<1 && is_array($this->id3v2->TLEN) )
        {
            $this->waveLength= ( $this->id3v2->TLEN['value'] / 1000 );
        }

        $this->id3Tag = $this->info["mpeg_id3v1_tag"];

        if ($this->id3Tag)
        {
            $this->id3Title = $this->info["mpeg_id3v1_tag"]["title"];
            $this->id3Artist = $this->info["mpeg_id3v1_tag"]["artist"];
            $this->id3Album = $this->info["mpeg_id3v1_tag"]["album"];
            $this->id3Year = $this->info["mpeg_id3v1_tag"]["year"];
            $this->id3Comment = $this->info["mpeg_id3v1_tag"]["comment"];
            $this->id3Genre = $this->info["mpeg_id3v1_tag"]["genre"];
        }
    }

    /**
     * // ************************************************************************
     * // longCalc calculates the decimal value of 4 bytes
     * // mode = 0 ... b1 is the byte with least value
     * // mode = 1 ... b1 is the byte with most value
     * // ************************************************************************
     */
    public function longCalc($b1,$b2,$b3,$b4,$mode): float|int
    {
        $b1 = hexdec(bin2hex((string) $b1));
        $b2 = hexdec(bin2hex((string) $b2));
        $b3 = hexdec(bin2hex((string) $b3));
        $b4 = hexdec(bin2hex((string) $b4));
        if ($mode == 0) {
            return ($b1 + ($b2*256) + ($b3 * 65536) + ($b4 * 16_777_216));
        }

        return ($b4 + ($b3*256) + ($b2 * 65536) + ($b1 * 16_777_216));
    }



    /**
     *
     * // ************************************************************************
     * // shortCalc calculates the decimal value of 2 bytes
     * // mode = 0 ... b1 is the byte with least value
     * // mode = 1 ... b1 is the byte with most value
     * // ************************************************************************
     *
     * @param $b1
     * @param $b2
     * @param $mode
     * @return float|int
     */
    public function shortCalc($b1,$b2,$mode): float|int
    {
        $b1 = hexdec(bin2hex((string) $b1));
        $b2 = hexdec(bin2hex((string) $b2));
        if ($mode == 0)
        {
            return ($b1 + ($b2*256));
        }

        return ($b2 + ($b1*256));
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

    // ************************************************************************
    // getVisualization creates a graphical visualization of the audio-sample
    // (works ONLY * for uncompressed waves!
    // * files with 1 or 2 channels
    // * 8/16/24/32 bit sample-resolution )
    // ************************************************************************

    public function getVisualization($output): void
    {
        $width=$this->visualWidth;
        $height=$this->visualHeight;
        $height_channel = $height / $this->waveChannels;
        if ($this->waveFilename != "" && $this->waveId == "RIFF" && $this->waveType == "WAVE" && ($this->waveChannels>=1 && $this->waveChannels<=2) && $this->waveBits%8==0)
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
                        ImageJpeg ($im,$output);
                    } else {
                        ImagePng ($im,$output);
                    }
                }
            }

            fclose ($file);
        }
    }

    // ************************************************************************
    // getSampleInfo extracts the attributes of the AudioFile-Object
    // ************************************************************************

    public function getSampleInfo ()
    {
        $valid = true;

        if (str_contains(strtoupper((string)$this->waveFilename), "MP3"))
        {
            $this->mp3info();
        } elseif (str_ends_with(strtoupper((string) $this->waveFilename), "OGG")) {
            $this->ogginfo ();
        } else {

            $this->waveSize = filesize ($this->waveFilename);
            if ($this->waveSize > 16)
            {
                $file = fopen ($this->waveFilename,"r");
                $chunk_id = fgetc($file) . fgetc($file) . fgetc($file) . fgetc($file);
                $null = fgetc($file) . fgetc($file) . fgetc($file) . fgetc($file);
                $chunk_id_2 = fgetc($file) . fgetc($file) . fgetc($file) . fgetc($file);
                $this->waveId = $chunk_id;
                $this->waveType = $chunk_id_2;
                if (str_starts_with($chunk_id, "PK")) {
                    // it's a ZIP-file
                    $this->waveId = "ZIP";
                    $this->waveType = "ZIP";
                    $this->valid = true;
                } elseif ($this->waveId == "RIFF" && $this->waveType == "WAVE") {
                    // it's a Wave-File
                    $chunk_id = fgetc($file) . fgetc($file) . fgetc($file) . fgetc($file);
                    $chunk_size = $this->longCalc (fgetc($file) , fgetc($file) , fgetc($file) , fgetc($file),0);
                    if ($chunk_id == "fmt ")
                    {
                        $format_len = $chunk_size;
                        $this->waveCompression = $this->shortCalc (fgetc ($file), fgetc ($file),0);
                        $this->waveChannels = $this->shortCalc (fgetc ($file), fgetc ($file),0);
                        $this->waveFramerate = $this->longCalc (fgetc ($file), fgetc ($file), fgetc ($file), fgetc ($file),0);
                        $this->waveByterate = $this->longCalc (fgetc ($file), fgetc ($file), fgetc ($file), fgetc ($file),0);
                        $null = fgetc($file) . fgetc($file);
                        $this->waveBits = $this->shortCalc (fgetc ($file), fgetc ($file),0);
                        $read = 16;
                        if ($read < $format_len)
                        {
                            $extra_bytes = $this->shortCalc (fgetc ($file), fgetc ($file),1);
                            $j = 0;
                            while ($j < $extra_bytes && !feof($file))
                            {
                                $null = fgetc ($file);
                                ++$j;
                            }
                        }

                        $chunk_id = fgetc($file) . fgetc($file) . fgetc($file) . fgetc($file);
                        $chunk_size = $this->longCalc (fgetc($file) , fgetc($file) , fgetc($file) , fgetc($file),0);
                        if ($chunk_id == "data")
                        {
                            $this->waveLength = (($chunk_size / $this->waveChannels) / ($this->waveBits/8)) / $this->waveFramerate;
                        } else {
                            while ($chunk_id != "data" && !feof($file))
                            {
                                $j = 1;
                                while ($j <= $chunk_size && !feof($file))
                                {
                                    $null = fgetc ($file);
                                    ++$j;
                                }

                                $chunk_id = fgetc($file) . fgetc($file) . fgetc($file) . fgetc($file);
                                //print "<br>$chunk_id*";
                                $chunk_size = $this->longCalc (fgetc($file) , fgetc($file) , fgetc($file) , fgetc($file),0);
                            }

                            if ($chunk_id == "data")
                            {
                                $this->waveLength = (($chunk_size / $this->waveChannels) / ($this->waveBits/8)) / $this->waveFramerate;
                            }

                        }
                    } else {
                        $valid = false;
                    }
                } elseif ($this->waveId == "FORM" && $this->waveType == "AIFF") {
                    // we have a AIFF file here
                    $chunk_id = fgetc($file) . fgetc($file) . fgetc($file) . fgetc($file);
                    $chunk_size = $this->longCalc (fgetc($file) , fgetc($file) , fgetc($file) , fgetc($file),0);
                    if ($chunk_id == "COMM")
                    {
                        $format_len = $chunk_size;
                        $this->waveChannels = $this->shortCalc (fgetc ($file), fgetc ($file),1);
                        $null = $this->longCalc (fgetc ($file), fgetc ($file), fgetc ($file), fgetc ($file),1);
                        $this->waveBits = $this->shortCalc (fgetc ($file), fgetc ($file),1);
                        $null = fgetc ($file) . fgetc ($file);
                        $this->waveFramerate = $this->shortCalc (fgetc ($file), fgetc ($file),1);

                        $read = 16;
                    } else {
                        $valid = false;
                    }
                } else {
                    // probably crap

                    $valid = false;
                }

                fclose ($file);
            } else {
                $valid = false;
            }

            return ($valid);
        }
    }

    // ************************************************************************
    // printSampleInfo prints the attributes of the AudioFile-Object
    // ************************************************************************

    public function printSampleInfo(): void
    {
        print "<table width=100% border=1>";
        print sprintf('<tr><td align=right>filename</td> <td>&nbsp;%s</td></tr>', $this->waveFilename);
        print sprintf('<tr><td align=right>id</td> <td>&nbsp;%s</td></tr>', $this->waveId);
        print sprintf('<tr><td align=right>type</td> <td>&nbsp;%s</td></tr>', $this->waveType);
        print sprintf('<tr><td align=right>size</td> <td>&nbsp;%s</td></tr>', $this->waveSize);
        print "<tr><td align=right>compression</td> <td>&nbsp;".$this->getCompression ($this->waveCompression)."</td></tr>";
        print sprintf('<tr><td align=right>channels</td> <td>&nbsp;%s</td></tr>', $this->waveChannels);
        print sprintf('<tr><td align=right>framerate</td> <td>&nbsp;%s</td></tr>', $this->waveFramerate);
        print sprintf('<tr><td align=right>byterate</td> <td>&nbsp;%s</td></tr>', $this->waveByterate);
        print sprintf('<tr><td align=right>bits</td> <td>&nbsp;%s</td></tr>', $this->waveBits);
        print "<tr><td align=right>length</td> <td>&nbsp;".number_format ($this->waveLength,"2")." sec.<br>&nbsp;".date("i:s", mktime(0,0,round($this->waveLength)))."</td></tr>";

        // ID3V1
        if ($this->id3Tag)
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
        }
        else
        {
            print "<tr><td align=right>id3v1-tags</td><td>Not found</td></tr>";
        }

        // ID3V2
        if ($this->id3v2)
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
        if ($this->waveId=="OGG")
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
        }
        else
        {
            print "<tr><td align=right>ogg vorbis info</td><td>Not found</td></tr>";
        }

        print "</table>";
    }

    // ************************************************************************
    // loadFile initializes the AudioFile-Object
    // ************************************************************************

    public function loadFile ($loadFilename): void
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


    // ************************************************************************
    // getId3v2 loads id3v2 frames into $this->id3v2-><frameid>
    // - any frame flags are saved in an array called <frameid>_flags
    // - for instance, song title will be in $this->id3v2->TIT2
    // and any flags set in TIT2 would be in array $this->id3v2->TIT2_flags
    //
    // For common frame id codes see http://www.id3.org/id3v2.4.0-frames.txt
    // For more info on format see http://www.id3.org/id3v2.4.0-structure.txt
    // ************************************************************************

    public function getId3v2(&$fp): int
    {
        // ID3v2 version 4 support -- see http://www.id3.org/id3v2.4.0-structure.txt
        $footer = 0;

        // id3v2 version
        $tmp = ord(fread($fp,1));
        $tmp2 = ord(fread($fp,1));
        $this->id3v2 = new Id3v2();
        $this->id3v2->version = "ID3v2.".$tmp.".".$tmp2;

        // flags
        $tmp = ord(fread($fp,1));
        if (($tmp & 128) !== 0) {
            $this->id3v2->unsynch = "1";
        }

        if (($tmp & 64) !== 0) {
            $this->id3v2->extended = "1";
        }

        if (($tmp & 32) !== 0) {
            $this->id3v2->experimental = "1";
        }

        if(($tmp & 16) !== 0)
        {
            $this->id3v2->footer = "1";
            $footer = 10;
        }

        // tag size
        $tagsize = $this->get32bitSynchsafe($fp) + $footer;

        // extended header
        if ($this->id3v2->extended==1)
        {
            // get extended header size
            $extended_header_size = $this->get32bitSynchsafe($fp) ;

            // load (but ignore) extended header
            $this->id3v2->extended_header= fread($fp, $extended_header_size);
        }

        // get the tag contents
        while ( ftell($fp) < ($tagsize+10) )
        {
            // get next frame header
            $frameid = fread($fp,4);
            if (trim($frameid)=="") {
                break;
            }

            $framesize= $this->get32bitSynchsafe($fp);
            $frameflags0= ord(fread($fp,1));
            $frameflags1= ord(fread($fp,1));

            // frame status flags
            $frameidflags= $frameid."_flags";
            if (($frameflags0 & 128) !== 0) {
                $this->id3v2->{$frameidflags}['tag_alter_discard'] = 1;
            }

            if (($frameflags0 & 64) !== 0) {
                $this->id3v2->{$frameidflags}['file_alter_discard'] = 1;
            }

            if (($frameflags0 & 32) !== 0) {
                $this->id3v2->{$frameidflags}['readonly'] = 1;
            }
            // frame format flags
            if (($frameflags1 & 128) !== 0) {
                $this->id3v2->{$frameidflags}['group'] = 1;
            }

            if (($frameflags1 & 16) !== 0) {
                $this->id3v2->{$frameidflags}['compressed'] = 1;
            }

            if (($frameflags1 & 8) !== 0) {
                $this->id3v2->{$frameidflags}['encrypted'] = 1;
            }

            if (($frameflags1 & 4) !== 0) {
                $this->id3v2->{$frameidflags}['unsyrchronised'] = 1;
            }

            if (($frameflags1 & 2) !== 0) {
                $this->id3v2->{$frameidflags}['data_length_indicator'] = 1;
            }

            // get frame contents
            $this->id3v2->{$frameid} = trim(fread($fp, $framesize));
        }

        // position $fp at end of id3v2header
        fseek($fp, ($tagsize + 10), SEEK_SET);
        return 1;
    }


    // ************************************************************************
    // get32bitSynchsafe returns a converted integer from an ID3v2 tag
    // ************************************************************************

    public function get32bitSynchsafe(&$fp): int
    {
        /* Synchsafe integers are
        integers that keep its highest bit (bit 7) zeroed, making seven bits
        out of eight available. Thus a 32 bit synchsafe integer can store 28
        bits of information.
        */
        $tmp = ord(fread($fp,1)) & 127;
        $tmp2 = ord(fread($fp,1)) & 127;
        $tmp3 = ord(fread($fp,1)) & 127;
        $tmp4 = ord(fread($fp,1)) & 127;
        return ($tmp * 2_097_152) + ($tmp2 * 16384) + ($tmp3 * 128) + $tmp4;
    }


    // ************************************************************************
    // ogginfo gets format, duration, and metadata from Ogg Vorbis files
    // - metadata (comment header) information is saved in
    // $this->vorbis_comment-><fieldname>
    // - for instance, the song title will be in $this->vorbis_comment->title
    // - WARNING: values may be arrays because the Vorbis spec allows multiple fields
    // with the same name (eg, $this->vorbis_comment->artist[0] and
    // $this->vorbis_comment->artist[1] for a duet)
    //
    // For more info on Ogg bitstream containers, see http://www.xiph.org/ogg/vorbis/doc/framing.html
    // For more info on Vorbis, see http://www.xiph.org/ogg/vorbis/doc/Vorbis_I_spec.html
    // ************************************************************************

    public function ogginfo(): int
    {
        $fp = fopen($this->waveFilename,"r");

        // Ogg stream?
        $capture_pattern= fread($fp,4);
        if ($capture_pattern!="OggS")
        {
            // not an Ogg stream
            fclose($fp);
            return 0;
        }

        rewind($fp);

        // find the next page, then
        $this->findVorbis($fp);
        $packet_type= ord(fread($fp,1));
        $preamble= fread($fp,6);

        if ($packet_type==1)
        {
            /* IDENTIFICATION HEADER
            1) [vorbis_version] = read 32 bits as unsigned integer
            2) [audio_channels] = read 8 bit integer as unsigned
            3) [audio_sample_rate] = read 32 bits as unsigned integer
            4) [bitrate_maximum] = read 32 bits as signed integer
            5) [bitrate_nominal] = read 32 bits as signed integer
            6) [bitrate_minimum] = read 32 bits as signed integer
            7) [blocksize_0] = 2 exponent (read 4 bits as unsigned integer) -- IGNORING
            8) [blocksize_1] = 2 exponent (read 4 bits as unsigned integer) -- IGNORING
            9) [framing_flag] = read one bit -- IGNORING
            */
            $identification= unpack('L1vorbis_version/C1audio_channels/L1audio_sample_rate/L1bitrate_maximum/L1bitrate_nominal/L1bitrate_minimum', fread($fp,21));
            //print "<pre>".print_r($identification,1)."</pre>";
        }

        // find the next header, then
        $this->findVorbis($fp);
        $packet_type= ord(fread($fp,1));
        $preamble= fread($fp,6);

        if ($packet_type==3)
        {
            /* COMMENT HEADER
                1) [vendor_length] = read an unsigned integer of 32 bits
                2) [vendor_string] = read a UTF-8 vector as [vendor_length] octets
                3) [user_comment_list_length] = read an unsigned integer of 32 bits
                4) iterate [user_comment_list_length] times {
                5) [length] = read an unsigned integer of 32 bits
                6) this iteration's user comment = read a UTF-8 vector as [length] octets
                }
                7) [framing_bit] = read a single bit as boolean

                Note that there may be more than one instance of any field
            */
            $vendor= unpack('L1vendor_length', fread($fp,4));
            $vendor['vendor_string']= fread($fp, $vendor['vendor_length']);
            $list= unpack('L1user_comment_list_length', fread($fp,4));
            for ($i=0; $i<$list['user_comment_list_length']; ++$i)
            {
                $length= unpack('L1length', fread($fp,4));
                $temp= fread($fp, $length['length']);
                $array= explode("=",$temp,2);

                // field names are case-insensitive
                $array[0]= strtoupper( $array[0] );

                /*
                EXPLANATION OF THE FOLLOWING LOGIC
                If there is only one artist field, it will be at $this->vorbis_comment->ARTIST, handled by the final else below
                If a second one is found, $this->vorbis_comment->ARTIST will be converted to an array with two artist values.
                    This is done by the if statement.
                Any additional artist fields will be pushed onto the end of the $this->vorbis_comment->ARTIST array by the elseif
                */

                if ($this->vorbisComment->{$array[0]}!="" && !is_array( $this->vorbisComment->{$array[0]}) )
                {
                    // second instance, convert to array
                    $temp= $this->vorbisComment->{$array[0]};
                    $this->vorbisComment->{$array[0]}= [$temp, $array[1]];
                }
                elseif (is_array( $this->vorbisComment->{$array[0]})) {
                    // third through nth instances, add to array
                    $this->vorbisComment->{$array[0]}[] = $array[1];
                }
                else
                {
                    // first instance
                    $this->vorbisComment->{$array[0]}= $array[1];
                }
            }

            //print "<pre>".print_r($this->vorbis_comment,1)."</pre>";
        }

        // find length (number of samples, ay?) -- last page will have total samples info, see below
        $filesize= filesize($this->waveFilename);
        $nearend = $filesize > 12288 ? -12288 : 0 - $filesize;

        fseek($fp, $nearend, SEEK_END);

        // look for page of type 4 or higher (0x04 == end-of-stream)
        while($type < 4 && !feof($fp)) {
            $type= $this->findOggPage($fp);
        }

        // found the end of stream page...
        // the next 8 bytes are the absolute granule position:
        /*
            "The position specified is the total samples encoded after
            including all packets finished on this page (packets begun
            on this page but continuing on to the next page do not count).
            The rationale here is that the position specified in the frame
            header of the last page tells how long the data coded by the
            bitstream is. "
        */
        $bytes[0]= ord(fread($fp,1));
        $bytes[1]= ord(fread($fp,1));
        $bytes[2]= ord(fread($fp,1));
        $bytes[3]= ord(fread($fp,1));
        $bytes[4]= ord(fread($fp,1));
        $bytes[5]= ord(fread($fp,1));
        $bytes[6]= ord(fread($fp,1));
        $bytes[7]= ord(fread($fp,1));
        foreach ($bytes AS $exp=>$value)
        {
            $samples+= ($value * 256 ** $exp);
        }

        $seconds= round(($samples / $identification['audio_sample_rate']), 2);
        $min= floor($seconds/60);
        $sec= $seconds - ($min * 60);
        $duration= sprintf('%s:%s', $min, $sec);
        //print "$samples samples / $seconds seconds ($duration)";

        fclose($fp);

        // transfer the extracted data into classAudioFile-structure
        $this->waveId = "OGG";
        $this->waveType = "Ogg Bitstream";
        $this->waveCompression = "Vorbis version 1.".$identification['vorbis_version'];
        $this->waveChannels = $identification['audio_channels'];
        $this->waveFramerate = $identification['audio_sample_rate'];
        $this->waveByterate = ($identification['bitrate_nominal']/1000)." Kbits/sec.";
        $this->waveBits = "n/a";
        $this->waveSize = $filesize;
        $this->waveLength = $seconds;
        return 1;
    }

    // ************************************************************************
    // findVorbis finds the start of the next Vorbis header in an Ogg bitstream
    // ************************************************************************

    public function findVorbis(&$fp): void
    {
        // find the next header, then
        $capture_pattern= fread($fp, 6);
        while ($capture_pattern!="vorbis" && !feof($fp))
        {
            // yes, character by character, fun!
            $capture_pattern= substr($capture_pattern, 1).fread($fp, 1);
            //print ". ";
        }

        //print "Found header ".(ftell($fp)-7)."<br>";

        // back up the pointer by 7 to start of header
        fseek($fp, -7, SEEK_CUR);
    }

    // ************************************************************************
    // findOggPage finds the next logical page in an Ogg bitstream, and returns the page type flag
    // ************************************************************************

    public function findOggPage(&$fp): int
    {
        // find the next header, then
        $capture_pattern= fread($fp, 4);
        while ($capture_pattern!="OggS" && !feof($fp))
        {
            // yes, character by character, fun!
            $capture_pattern= substr($capture_pattern, 1).fread($fp, 1);
            //print ". ";
        }

        $this->version = fread($fp,1);
        //print "Found page ".sprintf('%08b',$type)." ".(ftell($fp)-6)."<br>";
        return ord(fread($fp,1));
    }
}
