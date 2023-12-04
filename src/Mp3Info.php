<?php

namespace Masterfermin02\Audio;

use Masterfermin02\Audio\ValueObjects\Id3v2;

class Mp3Info
{
    public function __construct(
        public readonly array $byte = [],
        public readonly array $version = ["MPEG Version 2.5", false, "MPEG Version 2 (ISO/IEC 13818-3)", "MPEG Version 1 (ISO/IEC 11172-3)"],
        public readonly array $versionBitrate = [1, false, 1, 0],
        public readonly array $versionSampling = [2, false, 1, 0],
        public readonly array $layer = [false, "Layer III", "Layer II", "Layer I"],
        public readonly array $layerBitrate = [false, 2, 1, 0],
        public readonly array $layerLength = [false, 1, 1, 0],
        public readonly array $protection = ["Protected by CRC (16bit crc follows header)", "Not protected"],
        public readonly array $byteRate = [[["free", 32, 64, 96, 128, 160, 192, 224, 256, 288, 320, 352, 384, 416, 448, "bad"], ["free", 32, 48, 56, 64, 80, 96, 112, 128, 160, 192, 224, 256, 320, 384, "bad"], ["free", 32, 40, 48, 56, 64, 80, 96, 112, 128, 160, 192, 224, 256, 320, "bad"]], [["free", 32, 48, 56, 64, 80, 96, 112, 128, 144, 160, 176, 192, 224, 256, "bad"], ["free", 8, 16, 24, 32, 40, 48, 56, 64, 80, 96, 112, 128, 144, 160, "bad"], ["free", 8, 16, 24, 32, 40, 48, 56, 64, 80, 96, 112, 128, 144, 160, "bad"]]],
        public readonly array $samplingRate = [[44100, 48000, 32000, false], [22050, 24000, 16000, false], [11025, 12000, 8000, false]],
        public readonly array $channelMode = ["Stereo", "Joint stereo (Stereo)", "Dual channel (Stereo)", "Single channel (Mono)"],
        public readonly array $copyright = ["Audio is not copyrighted", "Audio is copyrighted "],
        public readonly array $original = ["Copy of original media", "Original media"],
        public readonly array $emphasis = ["none", "50/15 ms", false, "CCIT J.17 "],
        public readonly array $genre = ["Blues", "Classic Rock", "Country", "Dance", "Disco", "Funk", "Grunge", "Hip-Hop", "Jazz", "Metal", "New Age", "Oldies", "Other", "Pop", "R&B", "Rap", "Reggae", "Rock", "Techno", "Industrial", "Alternative", "Ska", "Death Metal", "Pranks", "Soundtrack", "Euro-Techno", "Ambient", "Trip-Hop", "Vocal", "Jazz+Funk", "Fusion", "Trance", "Classical", "Instrumental", "Acid", "House", "Game", "Sound Clip", "Gospel", "Noise", "Alternative Rock", "Bass", "Soul", "Punk", "Space", "Meditative", "Instrumental Pop", "Instrumental Rock", "Ethnic", "Gothic", "Darkwave", "Techno-Industrial", "Electronic", "Pop-Folk", "Eurodance", "Dream", "Southern Rock", "Comedy", "Cult", "Gangsta", "Top 40", "Christian Rap", "Pop/Funk", "Jungle", "Native US", "Cabaret", "New Wave", "Psychadelic", "Rave", "Showtunes", "Trailer", "Lo-Fi", "Tribal", "Acid Punk", "Acid Jazz", "Polka", "Retro", "Musical", "Rock & Roll", "Hard Rock", "Folk", "Folk-Rock", "National Folk", "Swing", "Fast Fusion", "Bebob", "Latin", "Revival", "Celtic", "Bluegrass", "Avantgarde", "Gothic Rock", "Progressive Rock", "Psychedelic Rock", "Symphonic Rock", "Slow Rock", "Big Band", "Chorus", "Easy Listening", "Acoustic", "Humour", "Speech", "Chanson", "Opera", "Chamber Music", "Sonata", "Symphony", "Booty Bass", "Primus", "Porn Groove", "Satire", "Slow Jam", "Club", "Tango", "Samba", "Folklore", "Ballad", "Power Ballad", "Rhytmic Soul", "Freestyle", "Duet", "Punk Rock", "Drum Solo", "Acapella", "Euro-House", "Dance Hall", "Goa", "Drum & Bass", "Club-House", "Hardcore", "Terror", "Indie", "BritPop", "Negerpunk", "Polsk Punk", "Beat", "Christian Gangsta Rap", "Heavy Metal", "Black Metal", "Crossover", "Contemporary Christian", "Christian Rock", "Merengue", "Salsa", "Trash Metal", "Anime", "Jpop", "Synthpop"]
    ) {
    }

    public function addByte($byte): self
    {
        return new static($byte);
    }
}
