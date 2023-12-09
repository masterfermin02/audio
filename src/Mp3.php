<?php

namespace Masterfermin02\Audio;

use Masterfermin02\Audio\ValueObjects\Id3v2;

class Mp3 extends Wave
{
    public ?Id3v2 $id3v2;

    public $waveId;

    public $waveType;

    public $waveCompression;

    public $waveChannels;

    public $waveFramerate;

    public $waveByterate;

    public $waveBits;

    public $waveSize;

    public $waveLength;

    public $id3Tag;

    public $id3Title;

    public $id3Artist;

    public $id3Album;

    public $id3Year;

    public $id3Comment;

    public $id3Genre;

    public $bytePrv;

    private array $info = [];
    public function __construct(
        public readonly File $file,
        public readonly Mp3Info $mp3Info,
    ) {
        $header = 0;
        $v1tag = 0;
        $tmp = $this->file->fRead(3);

        if($tmp == "ID3")
        {
            // id3v2 tag is present
            $this->getId3v2();

            // getId3v2 will position pointer at end of header
            $header= $this->file->fTell();

        } else {
            $this->file->fSeek(0);
            $this->id3v2 = null;
        }

        for ($x=0;$x<4;++$x)
        {
            $byte[$x] = ord($this->file->fRead(1));
        }

        $this->file->fSeek(-128, SEEK_END);
        $TAG = $this->file->fRead(128);
        $this->file->fClose();

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

        $tmp = $this->file->getSize();
        $tmp = $tmp->value() - $header - 4 - $v1tag;

        $this->info["mpeg_frames"] = "";
        $this->info["mpeg_playtime"] = "";
        if(!$this->info["mpeg_bitrate"] || $this->info["mpeg_bitrate"] == 'bad' || !$this->info["mpeg_sampling_rate"])
        {
            $this->info["mpeg_playtime"] = -1;
        } elseif($this->info["mpeg_bitrate"] == 'free') {
            $this->info["mpeg_playtime"] = -1;
        } else {
            $tmp2 = ((8 * $tmp) / 1000) / $this->info["mpeg_bitrate"];
            $this->info["mpeg_frames"] = floor($tmp/$this->info["mpeg_framelength"]);
            $tmp *= 8;
            if ($rate_tmp != 0) {
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
        $this->waveSize = $this->file->getSize();
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

        parent::__construct(
            $this->file->fileName,
            $this->file->getSize(),
            $this->waveId,
            $this->waveType,
            $this->waveCompression,
            $this->waveChannels,
            $this->waveFramerate,
            $this->waveByterate,
            $this->waveBits,
            $this->waveLength,
        );
    }

    /**
        // ************************************************************************
        // getId3v2 loads id3v2 frames into $this->id3v2-><frameid>
        // - any frame flags are saved in an array called <frameid>_flags
        // - for instance, song title will be in $this->id3v2->TIT2
        // and any flags set in TIT2 would be in array $this->id3v2->TIT2_flags
        //
        // For common frame id codes see http://www.id3.org/id3v2.4.0-frames.txt
        // For more info on format see http://www.id3.org/id3v2.4.0-structure.txt
        // ************************************************************************
     * @return int
    **/
    public function getId3v2(): int
    {
        // ID3v2 version 4 support -- see http://www.id3.org/id3v2.4.0-structure.txt
        $footer = 0;

        // id3v2 version
        $tmp = ord($this->file->fRead(1));
        $tmp2 = ord($this->file->fRead(1));
        $this->id3v2 = new Id3v2();
        $this->id3v2->version = "ID3v2.".$tmp.".".$tmp2;

        // flags
        $tmp = ord($this->file->fRead(1));
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
        $tagsize = $this->get32bitSynchsafe() + $footer;

        // extended header
        if ($this->id3v2->extended==1)
        {
            // get extended header size
            $extended_header_size = $this->get32bitSynchsafe();

            // load (but ignore) extended header
            $this->id3v2->extended_header= $this->file->fRead($extended_header_size);
        }

        // get the tag contents
        while($this->file->fTell() < ($tagsize+10) )
        {
            // get next frame header
            $frameid = $this->file->fRead(4);
            if (trim($frameid)=="") {
                break;
            }

            $framesize= $this->get32bitSynchsafe();
            $frameflags0= ord($this->file->fRead(1));
            $frameflags1= ord($this->file->fRead(1));

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
            $this->id3v2->{$frameid} = trim($this->file->fRead( $framesize));
        }

        // position $fp at end of id3v2header
        $this->file->fSeek($tagsize + 10);

        return 1;
    }

    /**
    // ************************************************************************
    // get32bitSynchsafe returns a converted integer from an ID3v2 tag
    // ************************************************************************
    **/
    public function get32bitSynchsafe(): int
    {
        /* Synchsafe integers are
        integers that keep its highest bit (bit 7) zeroed, making seven bits
        out of eight available. Thus a 32 bit synchsafe integer can store 28
        bits of information.
        */
        $tmp = ord($this->file->fRead(1)) & 127;
        $tmp2 = ord($this->file->fRead(1)) & 127;
        $tmp3 = ord($this->file->fRead(1)) & 127;
        $tmp4 = ord($this->file->fRead(1)) & 127;
        return ($tmp * 2_097_152) + ($tmp2 * 16384) + ($tmp3 * 128) + $tmp4;
    }
}
