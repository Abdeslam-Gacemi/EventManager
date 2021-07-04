<?php

/**
 * @author Abdeslam Gacemi <abdobling@gmail.com>
 */

namespace Tests;

use PHPUnit\Framework\TestCase;
use Tests\Fixtures\CustomListener;
use Abdeslam\EventManager\EventManager;
use Abdeslam\EventManager\Contracts\EventInterface;
use Abdeslam\EventManager\Event;
use Abdeslam\EventManager\Exceptions\EventNotFoundException;
use Abdeslam\EventManager\Exceptions\InvalidEventException;
use stdClass;

class EventManagerTest extends TestCase
{
    /** @var EventManager */
    protected $manager;

    const EVENT_NAME = 'post.create';

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new EventManager();
    }
    

    /**
     * @test
     */
    public function eventManagerInit()
    {
        $this->assertEmpty($this->manager->getEvents());
        $this->assertFalse($this->manager->getLazyLoadingStatus());

        $manager = new EventManager(true, ['post.create', 'post.delete']);
        $this->assertCount(2, $manager->getEvents());
        $this->assertTrue($manager->hasEvents());
        $this->assertTrue($manager->hasEvent('post.create'));
        $this->assertTrue($manager->hasEvent('post.delete'));
        $this->assertInstanceOf(EventInterface::class, $manager->getEvent('post.create'));
        $this->assertInstanceOf(EventInterface::class, $manager->getEvent('post.delete'));
        $this->expectException(InvalidEventException::class);
        $this->manager->addEvent(['invalid_event']);
    }

    /**
     * @test
     */
    public function eventManagerAddEvent()
    {
        $this->manager->addEvent(self::EVENT_NAME);
        $this->assertNotEmpty($this->manager->getEvents());
        $this->assertCount(1, $this->manager->getEvents());
        $this->assertInstanceOf(EventInterface::class, $this->manager->getEvent(self::EVENT_NAME));
        $this->manager->addEvent(new Event($this->manager, 'post.delete'));
        $this->assertCount(2, $this->manager->getEvents());
        $this->assertInstanceOf(EventInterface::class, $this->manager->getEvent('post.delete'));
    }

    /**
     * @test
     */
    public function eventManagerOn()
    {
        $this->manager->setLazyLoading(true);
        $this->manager->on('myEvent', CustomListener::class);
        $this->assertTrue($this->manager->hasEvent('myEvent'));
        $event = $this->manager->getEvent('myEvent');
        $this->assertTrue($event->hasListeners());
        $this->assertCount(1, $event->getListeners());
        $this->manager->on('*Event', function (EventInterface $e, $data) {
            return $e;
        });
        $this->assertCount(2, $event->getListeners());
    }

    /**
     * @test
     */
    public function eventManagerGetEvent()
    {
        $this->manager->addEvent(self::EVENT_NAME);
        $this->assertInstanceOf(EventInterface::class, $this->manager->getEvent(self::EVENT_NAME));
        $this->expectException(EventNotFoundException::class);
        $this->manager->getEvent('non_existing_event');
    }
    
    /**
     * @test
     */
    public function eventManagerGetEvents()
    {
        $this->assertEmpty($this->manager->getEvents());
        $this->manager->addEvent(self::EVENT_NAME);
        $this->assertNotEmpty($this->manager->getEvents());
        $this->assertCount(1, $this->manager->getEvents());
    }
    
    /**
     * @test
     */
    public function eventManagerHasEvent()
    {
        $this->assertFalse($this->manager->hasEvent(self::EVENT_NAME));
        $this->manager->addEvent(self::EVENT_NAME);
        $this->assertTrue($this->manager->hasEvent(self::EVENT_NAME));
    }
        
    /**
     * @test
     */
    public function eventManagerHasEvents()
    {
        $this->assertFalse($this->manager->hasEvents());
        $this->manager->addEvent(self::EVENT_NAME);
        $this->assertTrue($this->manager->hasEvents());
    }

    /**
     * @test
     */
    public function eventManagerRemoveEvent()
    {
        $this->manager->addEvent(self::EVENT_NAME);
        $this->assertTrue($this->manager->hasEvent(self::EVENT_NAME));
        $this->manager->removeEvent(self::EVENT_NAME);
        $this->assertFalse($this->manager->hasEvent(self::EVENT_NAME));
    }
        
    /**
     * @test
     */
    public function eventManagerSetLazyLoading()
    {
        $this->assertFalse($this->manager->getLazyLoadingStatus());
        $this->manager->setLazyLoading(true);
        $this->assertTrue($this->manager->getLazyLoadingStatus());
    }
        
    /**
     * @test
     */
    public function eventManagerDispatch()
    {
        $event = $this->manager->addEvent(self::EVENT_NAME);
        $event->addListener(function ($event) {
            echo 'dispatched';
            return $event;
        });
        $this->expectOutputString('dispatched');
        $this->manager->dispatch($event);
        $this->expectException(InvalidEventException::class);
        $this->manager->dispatch(new stdClass());
    }
        
    /**
     * @test
     */
    public function eventManagerEmit()
    {
        $this->manager
        ->addEvent(self::EVENT_NAME)
        ->addListener(function ($event, $data) {
            echo $data['message'];
            return $event;
        });
        $this->expectOutputString('Hello, world!');
        $this->manager->emit(self::EVENT_NAME, ['message' => 'Hello, world!']);
    }
}