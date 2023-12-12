<?php

namespace Masterfermin02\Audio;

class PrintBasicInfo
{
    public function __construct(
    ) {

    }
    public function printInfo(Wave $metaData): void
    {
        print "<table width=100% border=1>";
        print sprintf('<tr><td align=right>filename</td> <td>&nbsp;%s</td></tr>', $metaData->fileName);
        print sprintf('<tr><td align=right>id</td> <td>&nbsp;%s</td></tr>', $metaData->id);
        print sprintf('<tr><td align=right>type</td> <td>&nbsp;%s</td></tr>', $metaData->type);
        print sprintf('<tr><td align=right>size</td> <td>&nbsp;%s</td></tr>', $metaData->size->value());
        print "<tr><td align=right>compression</td> <td>&nbsp;".$this->getCompression($metaData->compression, $metaData)."</td></tr>";
        print sprintf('<tr><td align=right>channels</td> <td>&nbsp;%s</td></tr>', $metaData->channels);
        print sprintf('<tr><td align=right>framerate</td> <td>&nbsp;%s</td></tr>', $metaData->fameRate);
        print sprintf('<tr><td align=right>byterate</td> <td>&nbsp;%s</td></tr>', $metaData->byteRate);
        print sprintf('<tr><td align=right>bits</td> <td>&nbsp;%s</td></tr>', $metaData->bits);
        print "<tr><td align=right>length</td> <td>&nbsp;".number_format ($metaData->length,"2")." sec.<br>&nbsp;".date("i:s", mktime(0,0,round($metaData->length)))."</td></tr>";
    }

    public function getCompression(string $id, Wave $metaData): string
    {
        if ($metaData->id != "MPEG" && $metaData->id !="OGG") {
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
}
