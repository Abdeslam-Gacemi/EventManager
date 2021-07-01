<?php

/**
 * @author Abdeslam Gacemi <abdobling@gmail.com>
 */

namespace Tests;

use Exception;
use Throwable;
use PHPUnit\Framework\TestCase;
use Abdeslam\EventManager\Event;
use Abdeslam\EventManager\Listener;
use Abdeslam\EventManager\EventManager;
use Abdeslam\EventManager\Contracts\EventInterface;
use Abdeslam\EventManager\Exceptions\InvalidListenerException;

class ListenerTest extends TestCase
{
    /** @var Listener */
    protected $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->listener = new Listener();
    }

    /**
     * @test
     */
    public function ListenerPriority()
    {
        $this->assertSame(0, $this->listener->getPriority());
        $listener = $this->listener->setPriority(100);
        $this->assertSame(100, $this->listener->getPriority());
        $this->assertSame($listener, $this->listener);
    }
    
    /**
     * @test
     */
    public function ListenerCallback()
    {
        $this->assertNull($this->listener->getCallback());
        $callback = function ($event, $data) {
            return $event;
        };
        $listener = $this->listener->setCallback($callback);
        $this->assertNotNull($this->listener->getCallback());
        $this->assertSame($callback, $this->listener->getCallback());
        $this->assertSame($listener, $this->listener);
    }
    
    /**
     * @test
     */
    public function ListenerProcess()
    {
        $manager = new EventManager();
        $event = new Event($manager, 'post.create');
        $this->listener->setCallback(function (EventInterface $event, $data) {
            echo 'Hello, world!';
            return $event;
        });

        $this->expectOutputString('Hello, world!');
        $this->listener->process($event);

        $this->listener->setCallback(function (EventInterface $event, $data) {
            // callback does not return the event instance
        });
        $this->expectException(InvalidListenerException::class);
        $this->listener->process($event);
    }

    /**
     * @test
     */
    public function listenerCatchException()
    {
        $manager = new EventManager();
        $event = new Event($manager, 'post.create');
        $this->listener->catch(function (EventInterface $event, Throwable $e) {
            $eClass = get_class($e);
            echo "Exception $eClass caught";
            return false;
        });
        $this->listener->setCallback(function (EventInterface $event, array $data) {
            throw new Exception();
        });
        $this->listener->process($event);
        $this->expectOutputString('Exception Exception caught');
    }
}