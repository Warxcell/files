<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\EventListener;

use Arxy\FilesBundle\EventListener\DoctrineORMListener;
use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Tests\File;
use Arxy\FilesBundle\Tests\File2;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnClearEventArgs;
use PHPUnit\Framework\TestCase;

class DoctrineORMListenerTest extends TestCase
{
    private ManagerInterface $manager;
    private DoctrineORMListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = $this->createMock(ManagerInterface::class);
        $this->manager->method('getClass')->willReturn(File::class);
        $this->listener = new DoctrineORMListener($this->manager);
    }

    public function testSubscribedEvents()
    {
        $actual = $this->listener->getSubscribedEvents();

        $this->assertSame(
            [
                'postPersist',
                'preRemove',
                'onClear',
            ],
            $actual
        );
    }

    public function testPostPersist()
    {
        $file = new File('filename', 125, '12345', 'image/jpeg');

        $this->manager->expects($this->once())->method('moveFile')->with($file);

        $event = $this->createMock(LifecycleEventArgs::class);
        $event->method('getObject')->willReturn($file);

        $this->listener->postPersist($event);
    }

    public function testPostPersistAnotherClass()
    {
        $file = new File2('filename', 125, '12345', 'image/jpeg');
        $this->manager->expects($this->never())->method('moveFile');

        $event = $this->createMock(LifecycleEventArgs::class);
        $event->method('getObject')->willReturn($file);

        $this->listener->postPersist($event);
    }

    public function testPostPersistNonFile()
    {
        $this->manager->expects($this->never())->method('moveFile');

        $event = $this->createMock(LifecycleEventArgs::class);
        $event->method('getObject')->willReturn(new \stdClass());

        $this->listener->postPersist($event);
    }

    public function testPreRemove()
    {
        $file = new File('filename', 125, '12345', 'image/jpeg');

        $this->manager->expects($this->once())->method('remove')->with($file);

        $event = $this->createMock(LifecycleEventArgs::class);
        $event->method('getObject')->willReturn($file);

        $this->listener->preRemove($event);
    }

    public function testPreRemoveAnotherClass()
    {
        $file = new File2('filename', 125, '12345', 'image/jpeg');

        $this->manager->expects($this->never())->method('remove');

        $event = $this->createMock(LifecycleEventArgs::class);
        $event->method('getObject')->willReturn($file);

        $this->listener->preRemove($event);
    }

    public function testPreRemoveNonFile()
    {
        $this->manager->expects($this->never())->method('moveFile');

        $event = $this->createMock(LifecycleEventArgs::class);
        $event->method('getObject')->willReturn(new \stdClass());

        $this->listener->postPersist($event);
    }

    public function testOnClear()
    {
        $this->manager->expects($this->once())->method('clear');

        $event = $this->createMock(OnClearEventArgs::class);
        $this->listener->onClear($event);
    }
}
