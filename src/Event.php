<?php

namespace Abdeslam\EventManager;

use Abdeslam\EventManager\Contracts\EventInterface;
use Abdeslam\EventManager\Contracts\ListenerInterface;
use Abdeslam\EventManager\Contracts\EventManagerInterface;
use Abdeslam\EventManager\Exceptions\InvalidEventException;
use Abdeslam\EventManager\Exceptions\InvalidListenerException;
use Closure;
use ReflectionClass;
use Throwable;

class Event implements EventInterface
{
    /** @var EventManagerInterface */
    protected $manager;

    /** @var string */
    protected $name;

    /** @var array */
    protected $listeners = [];

    /** @var array */
    protected $attributes = [];

    /** @var bool */
    protected $propagationStopped = false;

    public function __construct(EventManagerInterface $manager, string $name, array $listeners = [])
    {
        if ($name == '') {
            throw new InvalidEventException("Invalid event name, name should not be empty");
        }
        $this->name = $name;
        $this->manager = $manager;
        foreach ($listeners as $listener) {
            $this->addListener($listener);
        }
    }

    public function addListener($listener, int $priority = 0, ?Closure $catcher = null): EventInterface
    {
        if ($listener instanceof ListenerInterface) {
           $listener->setPriority($priority);
            if ($catcher && method_exists($listener, 'catch')) {
                /** @var Listener $listener */
                $listener->catch($catcher);
            }
            $this->listeners[] = $listener;
            return $this;
        } elseif (is_callable($listener)) {
            $listener = new Listener($priority, $listener);
            if ($catcher) {
                $listener->catch($catcher);
            }
            $this->listeners[] = $listener;
            return $this;
        } elseif (is_string($listener)) {
            if ($this->manager->getLazyLoadingStatus()) {
                $this->resolveListenerForLazyLoading($listener);
                $this->listeners[] = [
                    'listener' => $listener,
                    'priority' => $priority,
                    'catcher' => $catcher
                ];
                return $this;
            }
        }
        $listenerType = gettype($listener);
        throw new InvalidListenerException("Listener must be either an instance of Abdeslam\EventManager\Contracts\ListenerInterface or a valid callable, $listenerType given");
    }

    public function hasListeners(): bool
    {
        return (bool) count($this->listeners);
    }

    public function getListeners(): array
    {
        return $this->listeners;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getManager(): EventManagerInterface
    {
        return $this->manager;
    }

    public function setAttribute(string $key, $value): EventInterface
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    public function setAttributes(array $attributes): EventInterface
    {
        $this->attributes = $attributes;
        return $this;
    }

    public function hasAttribute(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function removeAttribute(string $key): EventInterface
    {
        unset($this->attributes[$key]);
        return $this;
    }

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    public function setPropagationStopped(bool $flag): EventInterface
    {
        $this->propagationStopped = $flag;
        return $this;
    }

    public function emit(array $data = []): EventInterface
    {
        $event = $this;
        $listeners = array_map(function ($listener) {
            if (is_array($listener)) {
                $listenerInstance = new $listener['listener']($listener['priority']);
                if ($listener['catcher'] && method_exists($listenerInstance, 'catch')) {
                    $listenerInstance->catch($listener['catcher']);
                }
                return $listenerInstance;
            }
            return $listener;
        }, $this->listeners);

        uasort($listeners, function ($listenerA, $listenerB) {
            /** @var ListenerInterface $listenerA */
            /** @var ListenerInterface $listenerB */
            return $listenerB->getPriority() <=> $listenerA->getPriority();
        });
        foreach ($listeners as $listener) {
            /** @var ListenerInterface $listener */
            $listenerReturnedValue = $listener->process($event, $data);
            if ($event->isPropagationStopped() || $listenerReturnedValue === false) {
                break;
            }
            $event = $listenerReturnedValue;
        }
        return $event;
    }

    protected function resolveListenerForLazyLoading(string $listener): void
    {
        try {
            $reflect = new ReflectionClass($listener);
            if (!$reflect->implementsInterface(ListenerInterface::class) || !$reflect->isInstantiable()) {
                throw new InvalidListenerException("Listener must be a valid FQCN that implements Abdeslam\EventManager\Contracts\ListenerInterface");
            }
        } catch (Throwable $e) {
            throw new InvalidListenerException('Invalid listener, ' . $e->getMessage());
        }
    }
}
