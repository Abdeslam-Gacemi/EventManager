<?php

/**
 * @author Abdeslam Gacemi <abdobling@gmail.com>
 */

namespace Tests;

use ReflectionClass;
use PHPUnit\Framework\TestCase;
use Abdeslam\EventManager\Event;
use Tests\Fixtures\CustomListener;
use Abdeslam\EventManager\Listener;
use Tests\Fixtures\InvalidListener;
use Abdeslam\EventManager\EventManager;
use Abdeslam\EventManager\Contracts\EventInterface;
use Abdeslam\EventManager\Contracts\EventManagerInterface;
use Abdeslam\EventManager\Exceptions\InvalidEventException;
use Abdeslam\EventManager\Exceptions\InvalidListenerException;

class EventTest extends TestCase
{
    const EVENT_NAME = 'post.create';

    /** @var EventInterface */
    protected $event;

    /** @var EventManagerInterface */
    protected $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new EventManager();
        $this->event = new Event($this->manager, self::EVENT_NAME);
    }

    /**
     * @test
     */
    public function eventInit()
    {
        $manager = new EventManager();
        $event = new Event($manager, self::EVENT_NAME);
        $this->assertSame('post.create', $event->getName());
        $this->assertEmpty($event->getAttributes());
        $this->assertFalse($event->hasListeners());
        $this->assertFalse($event->isPropagationStopped());

        $this->expectException(InvalidEventException::class);
        $event = new Event($manager, '');
    }
    
    /**
     * @test
     */
    public function eventAddListenersThroughConstructor()
    {
        $manager = new EventManager();
        $manager->setLazyLoading(true); // allowing lazy loading listeners
        $listener1 = new class {
            public function doSomething(EventInterface $event, array $data)
            {
                return $event;
            }
        };
        $listener2 = new class extends Listener {
        };
        $event = new Event($manager, self::EVENT_NAME, [
            [$listener1, 'doSomething'], // a valid callable
            $listener2, // an instance of ListenerInterface
            CustomListener::class // FQCN of an instance of ListenerInterface to be lazyLoaded (when the event is dispatched)
        ]);
        $this->assertTrue($event->hasListeners());
        $this->assertCount(3, $event->getListeners());
        
        $manager->setLazyLoading(false); // turning lazyLoading off
        $this->expectException(InvalidListenerException::class);
        new Event($manager, self::EVENT_NAME, [
            CustomListener::class
        ]);
    }

    /**
     * @test
     */
    public function eventAddListener()
    {
        // adding a closure as a callable listener
        $this->event->addListener(function ($event, $data) {
            return $event;
        });
        $this->assertTrue($this->event->hasListeners());
        $this->assertNotEmpty($this->event->getListeners());
        $this->assertCount(1, $this->event->getListeners());
        // adding an instance of ListenerInterface as a listener
        $listener = new class extends Listener {
        };
        $this->event->addListener($listener);
        $this->assertCount(2, $this->event->getListeners());
        
        // adding an object and method as a callable listener
        $listener2 = new class {
            public function doSomething(EventInterface $event, array $data) {
                return $event;
            }
        };
        $this->event->addListener([$listener2, 'doSomething']);
        $this->assertCount(3, $this->event->getListeners());
        
        // setting lazy loading to true and adding a FQCN of an implementation of ListenerInterface as a lazy loaded listener
        $this->event->getManager()->setLazyLoading(true);
        $this->event->addListener(CustomListener::class);
        $this->assertCount(4, $this->event->getListeners());

        $this->event->getManager()->setLazyLoading(false);
        $this->expectException(InvalidListenerException::class);
        $this->event->addListener(CustomListener::class);
    }

    /**
     * @test
     */
    public function eventHasListeners()
    {
        $this->assertFalse($this->event->hasListeners());
        $this->event->addListener(function ($event, $data) {
            return $event;
        });
        $this->assertTrue($this->event->hasListeners());
    }

    /**
     * @test
     */
    public function eventGetListeners()
    {
        $this->assertEmpty($this->event->getListeners());
        $this->event->addListener(function ($event, $data) {
            return $event;
        });
        $this->assertNotEmpty($this->event->getListeners());
        $this->assertCount(1, $this->event->getListeners());
    }
    
    /**
     * @test
     */
    public function eventGetName()
    {
        $this->assertSame(self::EVENT_NAME, $this->event->getName());
    }
        
    /**
     * @test
     */
    public function eventGetManager()
    {
        $this->assertInstanceOf(EventManagerInterface::class, $this->event->getManager());
        $this->assertSame($this->manager, $this->event->getManager());
    }
            
    /**
     * @test
     */
    public function eventSetAttribute()
    {
        $this->event->setAttribute('model', 'UserModel');
        $this->assertTrue($this->event->hasAttribute('model'));
        $this->assertSame('UserModel', $this->event->getAttribute('model'));
    }
                
    /**
     * @test
     */
    public function eventSetAttributes()
    {
        $attrs = ['model' => 'UserModel'];
        $this->event->setAttributes($attrs);
        $this->assertSame($attrs, $this->event->getAttributes());
        $this->assertTrue($this->event->hasAttribute('model'));
        $this->assertSame('UserModel', $this->event->getAttribute('model'));
    }
                    
    /**
     * @test
     */
    public function eventGetAttribute()
    {
        $this->assertNull($this->event->getAttribute('non_existing_attribute'));
        $this->event->setAttribute('model', 'UserModel');
        $this->assertSame('UserModel', $this->event->getAttribute('model'));
    }
                    
    /**
     * @test
     */
    public function eventGetAttributes()
    {
        $attrs = ['model' => 'UserModel'];
        $this->assertIsArray($this->event->getAttributes());
        $this->assertEmpty($this->event->getAttributes());
        $this->event->setAttributes($attrs);
        $this->assertIsArray($this->event->getAttributes());
        $this->assertSame($attrs, $this->event->getAttributes());
    }
                    
    /**
     * @test
     */
    public function eventRemoveAttribute()
    {
        $attrs = [
            'model' => 'UserModel',
            'post.title' => 'lorem ipsum'
        ];
        $this->event->setAttributes($attrs);
        $this->event->removeAttribute('model');
        $this->assertFalse($this->event->hasAttribute('model'));
        $this->assertSame(['post.title' => 'lorem ipsum'], $this->event->getAttributes());
    }
                       
    /**
     * @test
     */
    public function eventPropagation()
    {
        $this->assertFalse($this->event->isPropagationStopped());
        $this->event->setPropagationStopped(true);
        $this->assertTrue($this->event->isPropagationStopped());
    }
                           
    /**
     * @test
     */
    public function eventPropagationStop()
    {
        // only the first listener get dispatched then it stops the propagation
        $this->event->addListener(function (EventInterface $event, array $data) {
            echo 'listener 1';
            $event->setPropagationStopped(true);
            return $event;
        })->addListener(function (EventInterface $event, array $data) {
            echo 'listener 2';
            return $event;
        });
        $this->expectOutputString('listener 1');
        $this->event->emit();
    }
                          
    /**
     * @test
     */
    public function eventEmit()
    {
        $this->event->addListener(function (EventInterface $event, array $data) {
            $event->setAttribute('listener 1', 'dispatched');
            return $event;
        });
        $this->event->addListener(function (EventInterface $event, array $data) {
            $event->setAttribute('listener 2', 'dispatched');
            return $event;
        });
        $this->event->emit();
        $this->assertSame(
            ['listener 1' => 'dispatched', 'listener 2' => 'dispatched'],
            $this->event->getAttributes()
        );
    }
                              
    /**
     * @test
     */
    public function eventEmitEventHasListenerWithPriority()
    {
        $this->event->addListener(function (EventInterface $event, array $data) {
            $event->setAttribute('listener 1', 'dispatched');
            return $event;
        });
        // adding listener with priority that get dispatched first
        $this->event->addListener(function (EventInterface $event, array $data) {
            $event->setAttribute('listener 2', 'dispatched');
            return $event;
        }, 10);
        $this->event->emit();
        $this->assertSame(
            ['listener 2' => 'dispatched', 'listener 1' => 'dispatched'],
            $this->event->getAttributes()
        );
    }
                                 
    /**
     * @test
     */
    public function eventEmitEventWithLazyLoading()
    {
        $this->event->getManager()->setLazyLoading(true);
        $this->event->addListener(CustomListener::class);
        $this->event->emit();
        $this->assertSame('from custom listener', $this->event->getAttribute('message'));
        // invalid listener FQCN
        $this->expectException(InvalidListenerException::class);
        $this->event->AddListener(InvalidListener::class);
    }

    /**
     * @test
     */
    public function eventResolveListenerForLazyLoading()
    {
        $reflect = new ReflectionClass($this->event);
        $method = $reflect->getMethod('resolveListenerForLazyLoading');
        $method->setAccessible(true);
        $this->expectException(InvalidListenerException::class);
        $method->invokeArgs($this->event, [InvalidListener::class]);
        $method->setAccessible(false);
    }
}
