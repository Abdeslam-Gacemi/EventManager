<?php

namespace Abdeslam\EventManager\Contracts;

interface ListenerInterface
{
    public function __construct(int $priority, ?callable $callback = null);
    public function getPriority(): int;
    public function setPriority(int $priority): ListenerInterface;
    public function getCallback(): ?callable;
    public function setCallback(callable $callback): ListenerInterface;
    public function process(EventInterface $event, array $data = []): EventInterface|bool;
}
