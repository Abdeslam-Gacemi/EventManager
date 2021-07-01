<?php

namespace Abdeslam\EventManager\Contracts;

use Closure;
use Psr\EventDispatcher\StoppableEventInterface;
use Abdeslam\EventManager\Contracts\ListenerInterface;

interface EventInterface extends StoppableEventInterface
{
    /**
     * @param EventManagerInterface $manager
     * @param string $name Event name
     * @param array $listeners an array of listeners
     */
    public function __construct(EventManagerInterface $manager , string $name, array $listeners = []);

    /**
     * attaches a listener to the event
     *
     * @param Callable|ListenerInterface|string $listener
     * @param integer $priority
     * @param Closure|null $catcher a function that catches any exception thrown by the listener
     * @return EventInterface
     */
    public function addListener($listener, int $priority = 0, ?Closure $catcher = null): EventInterface;

    /**
     * checks if the event has any listener
     *
     * @return boolean
     */
    public function hasListeners(): bool;

    /**
     * returns the array of listeners attached to the event
     *
     * @return array
     */
    public function getListeners(): array;

    /**
     * returns the name of the event
     *
     * @return string
     */
    public function getName(): string;

    /**
     * returns the manager instance
     *
     * @return EventManagerInterface
     */
    public function getManager(): EventManagerInterface;

    /**
     * adds an attribute to the event
     *
     * @param string $key
     * @param mixed $value
     * @return EventInterface
     */
    public function setAttribute(string $key, $value): EventInterface;

    /**
     * sets the attributes of the event
     *
     * @param array $attributes
     * @return EventInterface
     */
    public function setAttributes(array $attributes): EventInterface;

    /**
     * checks if the event has a given attribute by its key
     *
     * @param string $key
     * @return boolean
     */
    public function hasAttribute(string $key): bool;

    /**
     * retrieves an attribute by its key
     *
     * @param string $key
     * @return mixed
     */
    public function getAttribute(string $key): mixed;

    /**
     * returns the array of attributes
     *
     * @return array
     */
    public function getAttributes(): array;

    /**
     * removes an attribute by its ket
     *
     * @param string $key
     * @return EventInterface
     */
    public function removeAttribute(string $key): EventInterface;

    /**
     * sets the value of $propagationStopped
     *
     * @param boolean $flag
     * @return EventInterface
     */
    public function setPropagationStopped(bool $flag): EventInterface;

    /**
     * dispatches the event and calls the listener attached to it
     *
     * @param array $data an array of data to pass to each listener
     * @return EventInterface
     */
    public function emit(array $data = []): EventInterface;
}
