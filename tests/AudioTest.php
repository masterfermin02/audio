<?php

namespace Masterfermin02\Audio\Tests;

use _PHPStan_4c4f22f13\Nette\Neon\Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Masterfermin02\Audio\Audio;

class AudioTest extends TestCase
{
    private string $file;

    public function setUp(): void
    {
        $this->file = dirname(__DIR__) . '/tests/audios/file_example_MP3_700KB (1).mp3';
    }

    public function testAudioCanBeCreated(): void
    {
        $audio = Audio::create();
        $this->assertInstanceOf(Audio::class, $audio);
    }

    public function testAudioCanSetImageBaseDir(): void
    {
        $audio = Audio::create();
        $audio->setImageBaseDir('/path/to/dir');
        $this->assertEquals('/path/to/dir', $audio->imageBaseDir);
    }

    public function testAudioCanGetCompression(): void
    {
        $audio = Audio::create();
        $audio->loadFile($this->file);
        $this->assertEquals(0, $audio->getCompression(0));
    }

    public function testAudioCanLoadFile(): void
    {
        $audio = Audio::create();

        $audio->loadFile($this->file);
        $this->assertEquals($this->file, $audio->waveFilename);
    }

    public function testAudioThrowsExceptionForInvalidFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $audio = Audio::create();
        $audio->loadFile('invalid/path');
    }

  /*  public function testAudioCanGetVisualization(): void
    {
        $audio = Audio::create();
        $audio->loadFile($this->file);
        $this->assertStringEndsWith('.png', $audio->getVisualization('audiofile'));
    }*/

    public function testAudioCanGetSampleInfo(): void
    {
        $audio = Audio::create();
        $audio->loadFile($this->file);
        $this->assertTrue($audio->getSampleInfo());
    }

    public function testAudioCanPrintSampleInfo(): void
    {
        $audio = Audio::create();
        $audio->loadFile($this->file);
        $this->expectOutputRegex('/<table width=100% border=1>/');
        $audio->printSampleInfo();
    }
}
