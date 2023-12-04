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
            print "<a href=\"./audioTest.php?filename=$file\">$file</a><br>";
        } else {
        }
    }
}

print "</td><td valign=top>";

if (isset($_GET['filename']) &&  $_GET['filename'] <> "")
{
    $AF = new Audio;
    $AF->loadFile('./audios/' . $_GET['filename']);
    $AF->printSampleInfo();
    if ($AF->waveId == "RIFF")
    {
        $AF->visualWidth=600;
        $AF->visualHeight=500;
        $AF->getVisualization(substr($_GET['filename'],0,strlen($_GET['filename'])-4).".png");
        print "<img src=./".substr($_GET['filename'],0,strlen($_GET['filename'])-4).".png>";
    }
}
print "</td></tr></table></body></html>";
