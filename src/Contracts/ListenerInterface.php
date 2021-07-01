<?php

namespace Abdeslam\EventManager\Contracts;

use Abdeslam\EventManager\Contracts\EventInterface;

interface ListenerInterface
{
    /**
     * @param integer $priority
     * @param callable|null $callback a callable that handles the event
     */
    public function __construct(int $priority, ?callable $callback = null);

    /**
     * returns the priority of the listener
     *
     * @return integer
     */
    public function getPriority(): int;

    /**
     * sets the priority of the listener
     *
     * @param integer $priority
     * @return ListenerInterface
     */
    public function setPriority(int $priority): ListenerInterface;

    /**
     * returns the callback of the listener
     *
     * @return callable|null
     */
    public function getCallback(): ?callable;

    /**
     * sets the callback of the listener
     *
     * @param callable $callback
     * @return ListenerInterface
     */
    public function setCallback(callable $callback): ListenerInterface;

    /**
     * processes the event
     *
     * @param EventInterface $event
     * @param array $data
     * @return EventInterface|bool
     */
    public function process(EventInterface $event, array $data = []): EventInterface|bool;
}
