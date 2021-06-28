<?php

namespace Abdeslam\EventManager;

use Abdeslam\EventManager\Contracts\EventInterface;
use Abdeslam\EventManager\Contracts\EventManagerInterface;
use Abdeslam\EventManager\Contracts\ListenerInterface;
use Abdeslam\EventManager\Exceptions\EventNotFoundException;
use Abdeslam\EventManager\Exceptions\InvalidEventException;

class EventManager implements EventManagerInterface
{
    /** @var EventInterface[] */
    protected $events = [];

    /** @var bool */
    protected $lazyLoading = false;

    public function __construct(bool $lazyLoading = false, array $events = []) {
        $this->lazyLoading = $lazyLoading;
        foreach ($events as $event) {
            $this->addEvent($event, []);
        }
    }

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

    public function on(string $eventName, ListenerInterface|callable|string $listener, int $priority = 0) {
        $events = $this->resolveEvent($eventName);
        if (!$events) {
            $events = [$this->addEvent($eventName)];
        }
        /** @var EventInterface[] $events */
        foreach ($events as $event) {
            $event->addListener($listener, $priority);
        }
        return $this;
    }

    public function getEvent(string $event): EventInterface
    {
        if (!$this->hasEvent($event)) {
            throw new EventNotFoundException("Event $event not found");
        }
        return $this->events[$event];
    }

    public function getEvents(): array
    {
        return $this->events;
    }

    public function hasEvent(string $event): bool
    {
        return isset($this->events[$event]);
    }

    public function hasEvents(): bool
    {
        return (bool) count($this->events);
    }

    public function removeEvent(string $event): EventManagerInterface
    {
        unset($this->events[$event]);
        return $this;
    }

    public function setLazyLoading(bool $flag): EventManagerInterface
    {
        $this->lazyLoading = $flag;
        return $this;
    }

    public function getLazyLoadingStatus(): bool
    {
        return $this->lazyLoading;
    }

    public function dispatch(object $event)
    {
        if (!$event instanceof EventInterface) {
            throw new InvalidEventException("Event must be an instance of Abdeslam\EventManager\Contracts\EventInterface");
        }
        return $event->emit();
    }

    public function emit(string $event, array $data): EventInterface
    {
        return $this->getEvent($event)->emit($data);
    }

    protected function resolveEvent(string $eventName) {
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
