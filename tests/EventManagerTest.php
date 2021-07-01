<?php

/**
 * @author Abdeslam Gacemi <abdobling@gmail.com>
 */

namespace Tests;

use PHPUnit\Framework\TestCase;
use Abdeslam\EventManager\EventManager;
use Abdeslam\EventManager\Contracts\EventInterface;
use Abdeslam\EventManager\Contracts\EventManagerInterface;
use Abdeslam\EventManager\Exceptions\EventNotFoundException;

class EventManagerTest extends TestCase
{
    /** @var EventManagerInterface */
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