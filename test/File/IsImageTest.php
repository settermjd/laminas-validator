<?php

namespace LaminasTest\Validator\File;

use Laminas\Validator\File;
use PHPUnit\Framework\TestCase;

use function basename;
use function current;
use function extension_loaded;
use function is_array;

use const PHP_VERSION_ID;

/**
 * IsImage testbed
 *
 * @group      Laminas_Validator
 */
class IsImageTest extends TestCase
{
    protected function getMagicMime(): string
    {
        return __DIR__ . '/_files/magic.7.mime';
    }

    /**
     * @return array
     */
    public function basicBehaviorDataProvider()
    {
        $testFile   = __DIR__ . '/_files/picture.jpg';
        $fileUpload = [
            'tmp_name' => $testFile,
            'name'     => basename($testFile),
            'size'     => 200,
            'error'    => 0,
            'type'     => 'image/jpeg',
        ];
        return [
            //    Options, isValid Param, Expected value
            [null,                         $fileUpload, true],
            ['jpeg',                       $fileUpload, true],
            ['test/notype',                $fileUpload, false],
            ['image/gif, image/jpeg',      $fileUpload, true],
            [['image/vasa', 'image/jpeg'], $fileUpload, true],
            [['image/jpeg', 'gif'], $fileUpload, true],
            [['image/gif', 'gif'], $fileUpload, false],
            ['image/jp',                   $fileUpload, false],
            ['image/jpg2000',              $fileUpload, false],
            ['image/jpeg2000',             $fileUpload, false],
        ];
    }

    /**
     * Ensures that the validator follows expected behavior
     *
     * @dataProvider basicBehaviorDataProvider
     * @param mixed $options
     */
    public function testBasic($options, array $isValidParam, bool $expected): void
    {
        $validator = new File\IsImage($options);
        $validator->enableHeaderCheck();
        $this->assertEquals($expected, $validator->isValid($isValidParam));
    }

    /**
     * Ensures that the validator follows expected behavior for legacy Laminas\Transfer API
     *
     * @dataProvider basicBehaviorDataProvider
     * @param mixed $options
     */
    public function testLegacy($options, array $isValidParam, bool $expected): void
    {
        if (is_array($isValidParam)) {
            $validator = new File\IsImage($options);
            $validator->enableHeaderCheck();
            $this->assertEquals($expected, $validator->isValid($isValidParam['tmp_name'], $isValidParam));
        }
    }

    /**
     * Ensures that getMimeType() returns expected value
     *
     * @return void
     */
    public function testGetMimeType()
    {
        $validator = new File\IsImage('image/gif');
        $this->assertEquals('image/gif', $validator->getMimeType());

        $validator = new File\IsImage(['image/gif', 'video', 'text/test']);
        $this->assertEquals('image/gif,video,text/test', $validator->getMimeType());

        $validator = new File\IsImage(['image/gif', 'video', 'text/test']);
        $this->assertEquals(['image/gif', 'video', 'text/test'], $validator->getMimeType(true));
    }

    /**
     * Ensures that setMimeType() returns expected value
     *
     * @return void
     */
    public function testSetMimeType()
    {
        $validator = new File\IsImage('image/gif');
        $validator->setMimeType('image/jpeg');
        $this->assertEquals('image/jpeg', $validator->getMimeType());
        $this->assertEquals(['image/jpeg'], $validator->getMimeType(true));

        $validator->setMimeType('image/gif, text/test');
        $this->assertEquals('image/gif,text/test', $validator->getMimeType());
        $this->assertEquals(['image/gif', 'text/test'], $validator->getMimeType(true));

        $validator->setMimeType(['video/mpeg', 'gif']);
        $this->assertEquals('video/mpeg,gif', $validator->getMimeType());
        $this->assertEquals(['video/mpeg', 'gif'], $validator->getMimeType(true));
    }

    /**
     * Ensures that addMimeType() returns expected value
     *
     * @return void
     */
    public function testAddMimeType()
    {
        $validator = new File\IsImage('image/gif');
        $validator->addMimeType('text');
        $this->assertEquals('image/gif,text', $validator->getMimeType());
        $this->assertEquals(['image/gif', 'text'], $validator->getMimeType(true));

        $validator->addMimeType('jpg, to');
        $this->assertEquals('image/gif,text,jpg,to', $validator->getMimeType());
        $this->assertEquals(['image/gif', 'text', 'jpg', 'to'], $validator->getMimeType(true));

        $validator->addMimeType(['zip', 'ti']);
        $this->assertEquals('image/gif,text,jpg,to,zip,ti', $validator->getMimeType());
        $this->assertEquals(['image/gif', 'text', 'jpg', 'to', 'zip', 'ti'], $validator->getMimeType(true));

        $validator->addMimeType('');
        $this->assertEquals('image/gif,text,jpg,to,zip,ti', $validator->getMimeType());
        $this->assertEquals(['image/gif', 'text', 'jpg', 'to', 'zip', 'ti'], $validator->getMimeType(true));
    }

    /**
     * @Laminas-8111
     */
    public function testErrorMessages(): void
    {
        $files = [
            'name'     => 'picture.jpg',
            'type'     => 'image/jpeg',
            'size'     => 200,
            'tmp_name' => __DIR__ . '/_files/picture.jpg',
            'error'    => 0,
        ];

        $validator = new File\IsImage('test/notype');
        $validator->enableHeaderCheck();
        $this->assertFalse($validator->isValid(__DIR__ . '/_files/picture.jpg', $files));
        $error = $validator->getMessages();
        $this->assertArrayHasKey('fileIsImageFalseType', $error);
    }

    /**
     * @todo Restore test branches under PHP 8.1 when https://bugs.php.net/bug.php?id=81426 is resolved
     */
    public function testOptionsAtConstructor(): void
    {
        if (! extension_loaded('fileinfo')) {
            $this->markTestSkipped('This PHP Version has no finfo installed');
        }

        $magicFile = $this->getMagicMime();
        $options   = PHP_VERSION_ID >= 80100
            ? [
                'image/gif',
                'image/jpg',
                'enableHeaderCheck' => true,
            ]
            : [
                'image/gif',
                'image/jpg',
                'magicFile'         => $magicFile,
                'enableHeaderCheck' => true,
            ];

        $validator = new File\IsImage($options);

        if (PHP_VERSION_ID < 80100) {
            $this->assertEquals($magicFile, $validator->getMagicFile());
        }

        $this->assertTrue($validator->getHeaderCheck());
        $this->assertEquals('image/gif,image/jpg', $validator->getMimeType());
    }

    public function testNonMimeOptionsAtConstructorStillSetsDefaults(): void
    {
        $validator = new File\IsImage([
            'enableHeaderCheck' => true,
        ]);

        $this->assertNotEmpty($validator->getMimeType());
    }

    /**
     * @group Laminas-11258
     */
    public function testLaminas11258(): void
    {
        $validator = new File\IsImage();
        $this->assertFalse($validator->isValid(__DIR__ . '/_files/nofile.mo'));
        $this->assertArrayHasKey('fileIsImageNotReadable', $validator->getMessages());
        $this->assertStringContainsString('does not exist', current($validator->getMessages()));
    }
}
