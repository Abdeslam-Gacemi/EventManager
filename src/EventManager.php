<?php

/**
 * @author Abdeslam Gacemi <abdobling@gmail.com>
 */

namespace Abdeslam\EventManager;

use Abdeslam\EventManager\Contracts\EventInterface;
use Abdeslam\EventManager\Contracts\EventManagerInterface;
use Abdeslam\EventManager\Contracts\ListenerInterface;
use Abdeslam\EventManager\Exceptions\EventNotFoundException;
use Abdeslam\EventManager\Exceptions\InvalidEventException;

class EventManager implements EventManagerInterface
{
    /**
     * @var EventInterface[] 
     */
    protected $events = [];

    /**
     * @var bool 
     */
    protected $lazyLoading = false;

    /**
     * @param boolean $lazyLoading
     * @param array $events
     */
    public function __construct(bool $lazyLoading = false, array $events = [])
    {
        $this->lazyLoading = $lazyLoading;
        foreach ($events as $event) {
            $this->addEvent($event, []);
        }
    }

    /**
     * attaches an event to the manager
     *
     * @param EventInterface|string $event
     * @param array $attributes
     * @return EventInterface
     */
    public function addEvent($event, array $attributes = []): EventInterface
    {
        if ($event instanceof EventInterface) {
            $event->setAttributes($attributes);
            $this->events[$event->getName()] = $event;
        } elseif (is_string($event)) {
            $event = new Event($this, $event);
            $event->setAttributes($attributes);
            $this->events[$event->getName()] = $event;
        } else {
            throw new InvalidEventException("Error statement");
        }
        return $event;
    }

    /**
     * attaches an event with a listener to the manager
     * 
     * @param string $eventName
     * @param ListenerInterface|callable|string $listener
     * @param int $priority
     */
    public function on(string $eventName, ListenerInterface|callable|string $listener, int $priority = 0)
    {
        $events = $this->resolveEvent($eventName);
        if (!$events) {
            $events = [$this->addEvent($eventName)];
        }
        /**
         * @var EventInterface[] $events 
        */
        foreach ($events as $event) {
            $event->addListener($listener, $priority);
        }
        return $this;
    }

    /**
     * gets an event by its name
     *
     * @param string $event
     * @return EventInterface
     * @throws EventNotFoundException
     */
    public function getEvent(string $eventName): EventInterface
    {
        if (!$this->hasEvent($eventName)) {
            throw new EventNotFoundException("Event $eventName not found");
        }
        return $this->events[$eventName];
    }

    /**
     * returns the array of events
     *
     * @return array
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * checks if the manager has an event by its name
     *
     * @param string $event
     * @return boolean
     */
    public function hasEvent(string $eventName): bool
    {
        return isset($this->events[$eventName]);
    }

    /**
     * checks if the manager has any events
     *
     * @return boolean
     */
    public function hasEvents(): bool
    {
        return (bool) count($this->events);
    }

    /**
     * removes an event by its name
     *
     * @param string $event
     * @return EventManagerInterface
     */
    public function removeEvent(string $eventName): EventManagerInterface
    {
        unset($this->events[$eventName]);
        return $this;
    }

    /**
     * sets the lazy loading to true or false
     *
     * @param boolean $flag
     * @return EventManagerInterface
     */
    public function setLazyLoading(bool $flag): EventManagerInterface
    {
        $this->lazyLoading = $flag;
        return $this;
    }

    /**
     * returns the lazy loading status
     *
     * @return boolean
     */
    public function getLazyLoadingStatus(): bool
    {
        return $this->lazyLoading;
    }

    /**
     * Dispatches an event
     *
     * @param object $event
     * @return EventInterface
     */
    public function dispatch(object $event)
    {
        if (!$event instanceof EventInterface) {
            throw new InvalidEventException("Event must be an instance of Abdeslam\EventManager\Contracts\EventInterface");
        }
        return $event->emit();
    }

    /**
     * emits an event by its name
     *
     * @param string $event event name
     * @param array $data data to pass to the listeners attached to the event
     * @return EventInterface 
     */
    public function emit(string $event, array $data = []): EventInterface
    {
        return $this->getEvent($event)->emit($data);
    }

    /**
     * resolves the event name
     *
     * @param string $eventName
     * @return void
     */
    protected function resolveEvent(string $eventName)
    {
        $dynamic = false;
        if (strpos($eventName, '*') !== false) {
            $dynamic = true;
            $eventName = str_replace('\*', '(.*?)', preg_quote($eventName));
        }
        if (!$dynamic) {
            return $this->hasEvent($eventName) ? [$this->getEvent($eventName)] : [];
        } else {
            $events = [];
            foreach ($this->getEvents() as $event) {
                if (preg_match("|$eventName|", $event->getName())) {
                    $events[] = $event;
                }
            }
            return $events;
        }
    }
}
