<?php

namespace Abdeslam\EventManager\Contracts;

use Psr\EventDispatcher\EventDispatcherInterface;

interface EventManagerInterface extends EventDispatcherInterface
{
    public function addEvent($event, array $attributes = []): EventInterface;
    public function getEvent(string $event): EventInterface;
    public function getEvents(): array;
    public function hasEvent(string $event): bool;
    public function hasEvents(): bool;
    public function removeEvent(string $event): EventManagerInterface;
    public function setLazyLoading(bool $flag): EventManagerInterface;
    public function getLazyLoadingStatus(): bool;
    public function emit(string $event, array $data): EventInterface;
}
