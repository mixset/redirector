<?php

namespace Tests\Unit;

use Freeq\Redirector\Manager;
use PHPUnit\Framework\TestCase;
use Freeq\Redirector\Storages\FileStorage;
use Freeq\Redirector\Contracts\Redirectable;

class ManagerTest extends TestCase
{
    private $path;
    private $redirect;
    private $manager;

    public function setUp()
    {
        parent::setUp();

        $this->path = __DIR__ . '/redirects';
        $this->redirect = $this->getMockBuilder(Redirectable::class)->getMock();
        $this->redirect->method('hash')->willReturn(md5('myroute'));
        $this->redirect->method('routeFrom')->willReturn('myroute');
        $this->redirect->method('routeTo')->willReturn('to');
        $this->redirect->method('statusHttp')->willReturn(302);

        mkdir($this->path, 0775);
        $storage = (new FileStorage($this->path))->setRedirect($this->redirect);
        $this->manager = new Manager($storage);
    }

    public function test_Constructor_Ok()
    {
        $this->assertInstanceOf(Manager::class, $this->manager);
    }

    public function test_Store_Ok()
    {
        $this->manager->store();

        $this->assertTrue(file_exists($this->path . '/' . md5('myroute')));
    }

    public function test_Delete_Ok()
    {
        touch($this->path . '/' . md5('myroute'));
        $this->manager->delete();

        $this->assertFalse(file_exists($this->path . '/' . md5('myroute')));
    }

    public function test_Flush_Ok()
    {
        touch($this->path . '/' . 'file1');
        touch($this->path . '/' . 'file2');
        touch($this->path . '/' . 'file3');

        $this->manager->flush();

        $this->assertEquals(count(scandir($this->path)), 2);
    }

    public function tearDown()
    {
        array_map('unlink', glob(__DIR__ . '/redirects/*'));
        rmdir($this->path);

        parent::tearDown();
    }
}
