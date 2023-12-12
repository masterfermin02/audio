<?php

namespace Masterfermin02\Audio;

class Ogg extends Wave implements MetaData
{
    public $waveId;
    public $waveType;

    public $waveCompression;

    public $waveChannels;

    public $waveFramerate;

    public $waveByterate;

    public $waveBits;

    public $waveSize;

    public $waveLength;

    public array $vorbisComment;

    public string $version;

    public function __construct(
        public readonly File $file,
        public readonly PrintBasicInfo $printBasicInfo,
    ) {
        // Ogg stream?
        $capture_pattern = $this->file->fRead(4);
        if ($capture_pattern != "OggS")
        {
            // not an Ogg stream
            $this->file->fClose();
            throw new \InvalidArgumentException('The file is not an ogg file');
        }

        $this->file->rewind();

        // find the next page, then
        $this->findVorbis();
        $packet_type= ord($this->file->fRead(1));
        $preamble= $this->file->fRead(6);

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
            $identification= unpack(
                'L1vorbis_version/C1audio_channels/L1audio_sample_rate/L1bitrate_maximum/L1bitrate_nominal/L1bitrate_minimum',
                $this->file->fRead(21)
            );
            //print "<pre>".print_r($identification,1)."</pre>";
        }

        // find the next header, then
        $this->findVorbis();
        $packet_type= ord($this->file->fRead(1));
        $preamble= $this->file->fRead(6);

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
            $vendor= unpack('L1vendor_length', $this->file->fRead(4));
            $vendor['vendor_string']= $this->file->fRead( $vendor['vendor_length']);
            $list= unpack('L1user_comment_list_length', $this->file->fRead(4));
            for ($i=0; $i<$list['user_comment_list_length']; ++$i)
            {
                $length= unpack('L1length', $this->file->fRead(4));
                $temp= $this->file->fRead( $length['length']);
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

                if (isset($this->vorbisComment[$array[0]]) && !is_array($this->vorbisComment[$array[0]])) {
                    // second instance, convert to array
                    $temp= $this->vorbisComment[$array[0]];
                    $this->vorbisComment[$array[0]] = [$temp, $array[1]];
                } elseif (isset($this->vorbisComment[$array[0]]) && is_array($this->vorbisComment[$array[0]])) {
                    // third through nth instances, add to array
                    $this->vorbisComment[$array[0]][] = $array[1];
                } else {
                    // first instance
                    $this->vorbisComment[$array[0]] = $array[1];
                }
            }

            //print "<pre>".print_r($this->vorbis_comment,1)."</pre>";
        }

        // find length (number of samples, ay?) -- last page will have total samples info, see below
        $filesize= $this->file->getSize();
        $nearend = $filesize->value() > 12288 ? -12288 : 0 - $filesize->value();

        $this->file->fSeek($nearend, SEEK_END);

        // look for page of type 4 or higher (0x04 == end-of-stream)
        $type = 0;
        while($type < 4 && !$this->file->endOfFile()) {
            $type= $this->findOggPage();
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
        $bytes[0]= ord($this->file->fRead(1));
        $bytes[1]= ord($this->file->fRead(1));
        $bytes[2]= ord($this->file->fRead(1));
        $bytes[3]= ord($this->file->fRead(1));
        $bytes[4]= ord($this->file->fRead(1));
        $bytes[5]= ord($this->file->fRead(1));
        $bytes[6]= ord($this->file->fRead(1));
        $bytes[7]= ord($this->file->fRead(1));
        $samples = 0;

        foreach ($bytes AS $exp=>$value)
        {
            $samples += ($value * 256 ** $exp);
        }

        $seconds= round(($samples / $identification['audio_sample_rate']), 2);
        $min= floor($seconds/60);
        $sec= $seconds - ($min * 60);
        $duration= sprintf('%s:%s', $min, $sec);
        //print "$samples samples / $seconds seconds ($duration)";

        $this->file->fClose();

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
    // findVorbis finds the start of the next Vorbis header in an Ogg bitstream
    // ************************************************************************
    **/
    public function findVorbis(): void
    {
        // find the next header, then
        $capture_pattern= $this->file->fRead( 6);
        while ($capture_pattern!="vorbis" && !$this->file->endOfFile())
        {
            // yes, character by character, fun!
            $capture_pattern= substr($capture_pattern, 1) . $this->file->fRead( 1);
            //print ". ";
        }

        //print "Found header ".(ftell($fp)-7)."<br>";

        // back up the pointer by 7 to start of header
        $this->file->fSeek(-7, SEEK_CUR);
    }
    /**
    // ************************************************************************
    // findOggPage finds the next logical page in an Ogg bitstream, and returns the page type flag
    // ************************************************************************
    **/
    public function findOggPage(): int
    {
        // find the next header, then
        $capture_pattern= $this->file->fRead(4);
        while ($capture_pattern!="OggS" && !$this->file->endOfFile())
        {
            // yes, character by character, fun!
            $capture_pattern= substr($capture_pattern, 1) . $this->file->fRead(1);
            //print ". ";
        }

        $this->version = $this->file->fRead(1);
        //print "Found page ".sprintf('%08b',$type)." ".(ftell($fp)-6)."<br>";
        return ord($this->file->fRead(1));
    }

    public function printInfo(): void
    {
        $this->printBasicInfo->printInfo($this);

        // VORBIS
        if ($this->id == "OGG")
        {
            print "<tr><td align=right>ogg-tags</td><td>";
            print "<table width=100% border=1>";
            print "<tr><td width=70 align=right>title</td><td>&nbsp;".$this->vorbisComment['TITLE']."</td></tr>";
            print "<tr><td align=right>artist</td><td>&nbsp;".$this->vorbisComment['ARTIST']."</td></tr>";
            print "<tr><td align=right>album</td><td>&nbsp;".$this->vorbisComment['ALBUM']."</td></tr>";
            print "<tr><td align=right>date</td><td>&nbsp;".$this->vorbisComment['DATE']."</td></tr>";
            print "<tr><td align=right>genre</td><td>&nbsp;".$this->vorbisComment['GENRE']."</td></tr>";
            print "<tr><td align=right>comment</td><td>&nbsp;". ($this->vorbisComment['COMMENT'] ?? '')."</td></tr>";
            print "</table>";
            print "</td></tr>";
        } else {
            print "<tr><td align=right>ogg vorbis info</td><td>Not found</td></tr>";
        }

        print "</table>";
    }

    public function visualize(): void
    {
        // TODO: Implement visualize() method.
    }
}
