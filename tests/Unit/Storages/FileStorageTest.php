<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Freeq\Redirector\Storages\FileStorage;
use Freeq\Redirector\Contracts\Redirectable;
use Freeq\Redirector\Storages\AbstractStorage;

class FileStorageTest extends TestCase
{
    private $storage_path;
    private $redirect;

    public function setUp()
    {
        parent::setUp();

        $this->storage_path = __DIR__ . '/redirects';

        mkdir($this->storage_path, 0775);

        $this->redirect = $this->getMockBuilder(Redirectable::class)
            ->getMock();

        $this->redirect->method('hash')
            ->willReturn(md5('filename'));
    }

    /**
     * @expectedException Freeq\Redirector\Exceptions\CannotUseFileStorage
     */
    public function test_Constructor_NotWritable_Throws()
    {
        if (is_dir(__DIR__ . '/test')) {
            mkdir(__DIR__ . '/test', 0555);
        }

        new FileStorage(__DIR__ . '/test');

    }

    public function test_SetRedirect_Redirectable_Ok()
    {
        $storage = new FileStorage(__DIR__);
        $storage->setRedirect($this->redirect);

        $this->assertInstanceOf(AbstractStorage::class, $storage);
    }

    public function test_Store_CustomHash_Ok()
    {
        $storage = new FileStorage($this->storage_path);
        $storage->setRedirect($this->redirect);
        $storage->store();

        $this->assertTrue(file_exists($this->storage_path . '/' . md5('filename')));
    }

    public function test_Get_ValidAndInvalidHash()
    {
        $storage = new FileStorage($this->storage_path);
        $storage->setRedirect($this->redirect);
        $storage->store();

        $valid = $storage->get('filename');
        $invalid = $storage->get('wrongfilename');

        $this->assertEmpty($invalid);
        $this->assertTrue(is_null($invalid));
        $this->assertArrayHasKey('from', $valid);
        $this->assertArrayHasKey('to', $valid);
        $this->assertArrayHasKey('from', $valid);
    }

    public function test_Delete_Ok()
    {
        $storage = new FileStorage($this->storage_path);
        $storage->setRedirect($this->redirect);
        $storage->store();
        $storage->delete();

        $this->assertFalse(file_exists($this->storage_path . '/filename'));
    }

    public function test_Flush_Ok()
    {
        $storage = new FileStorage($this->storage_path);
        $storage->setRedirect($this->redirect);
        touch($this->storage_path . '/flush_me');
        $storage->flush();

        $this->assertFalse(file_exists($this->storage_path . '/flush_me'));
    }

    public function tearDown()
    {
        if (is_dir($this->storage_path)) {
            array_map('unlink', glob($this->storage_path . '/*'));
            rmdir($this->storage_path);
        }

        if (is_dir(__DIR__ . '/test')) {
            rmdir(__DIR__ . '/test');    
        }

        parent::tearDown();
    }
}
