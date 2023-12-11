<?php

use Masterfermin02\Audio\Audio;

require_once '../vendor/autoload.php';

print "<html><head>";
print "<link rel=\"stylesheet\" type=\"text/css\" href=\"./global.css\">";
print "<META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">";
print "<META HTTP-EQUIV=\"Expires\" CONTENT=\"-1\">";
print "</head><body>";

print "<table border=1>";
print "<tr><td valign=top>";

$handle=opendir('./audios/');
while (false !== ($file = readdir($handle)))
{
    if ($file <> "." && $file <> "..")
    {
        if ( (substr(strtoupper($file),strlen($file)-4,4)==".WAV") ||
            (substr(strtoupper($file),strlen($file)-4,4)==".AIF") ||
            (substr(strtoupper($file),strlen($file)-4,4)==".OGG") ||
            (substr(strtoupper($file),strlen($file)-4,4)==".MP3") )
        {
            print "<a href=\"./audio.php?filename=$file\">$file</a><br>";
        } else {
        }
    }
}

print "</td><td valign=top>";

if (isset($_GET['filename']) &&  $_GET['filename'] <> "")
{
    $audio = Audio::create();
    $filename = './audios/' .$_GET['filename'];
    $audio->loadFile($filename);
    $audio->printSampleInfo();

    if ($audio->wave->id == "RIFF")
    {
        $imageSrc = $audio->getVisualization($filename);
        print "<img src='./$imageSrc' alt='image generated.' />";
    }
}
print "</td></tr></table></body></html>";
