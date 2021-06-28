<?php

namespace Abdeslam\EventManager\Contracts;

use Closure;
use Psr\EventDispatcher\StoppableEventInterface;

interface EventInterface extends StoppableEventInterface
{
    public function __construct(EventManagerInterface $manager , string $name, array $listeners = []);
    public function addListener($listener, int $priority = 0, ?Closure $catcher = null): EventInterface;
    public function hasListeners(): bool;
    public function getListeners(): array;
    public function getName(): string;
    public function getManager(): EventManagerInterface;
    public function setAttribute(string $key, $value): EventInterface;
    public function setAttributes(array $attributes): EventInterface;
    public function hasAttribute(string $key): bool;
    public function getAttribute(string $key): mixed;
    public function getAttributes(): array;
    public function removeAttribute(string $key): EventInterface;
    public function setPropagationStopped(bool $flag): EventInterface;
    public function emit(array $data = []): EventInterface;
}
