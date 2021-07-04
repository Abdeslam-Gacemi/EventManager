<?php

/**
 * @author Abdeslam Gacemi <abdobling@gmail.com>
 */

namespace Abdeslam\EventManager;

use Closure;
use Throwable;
use ReflectionClass;
use Abdeslam\EventManager\Listener;
use Abdeslam\EventManager\Contracts\EventInterface;
use Abdeslam\EventManager\Contracts\ListenerInterface;
use Abdeslam\EventManager\Contracts\EventManagerInterface;
use Abdeslam\EventManager\Exceptions\InvalidEventException;
use Abdeslam\EventManager\Exceptions\InvalidListenerException;

class Event implements EventInterface
{
    /**
     * @var EventManagerInterface 
     */
    protected $manager;

    /**
     * @var string 
     */
    protected $name;

    /**
     * @var array 
     */
    protected $listeners = [];

    /**
     * @var array 
     */
    protected $attributes = [];

    /**
     * @var bool 
     */
    protected $propagationStopped = false;

    /**
     * @inheritDoc
     * @throws InvalidEventException
     */
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

    /**
     * @inheritDoc
     * @throws InvalidListenerException
     */
    public function addListener($listener, int $priority = 0, ?Closure $catcher = null): EventInterface
    {
        if ($listener instanceof ListenerInterface) {
            $listener->setPriority($priority);
            if ($catcher && method_exists($listener, 'catch')) {
                /**
                * @var Listener $listener 
                */
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
        throw new InvalidListenerException("Listener must be either a FQCN or an instance of Abdeslam\EventManager\Contracts\ListenerInterface or a valid callable, $listenerType given");
    }

    /**
     * @inheritDoc
     */
    public function hasListeners(): bool
    {
        return (bool) count($this->listeners);
    }

    /**
     * @inheritDoc
     */
    public function getListeners(): array
    {
        return $this->listeners;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function getManager(): EventManagerInterface
    {
        return $this->manager;
    }

    /**
     * @inheritDoc
     */
    public function setAttribute(string $key, $value): EventInterface
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setAttributes(array $attributes): EventInterface
    {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function hasAttribute(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    /**
     * @inheritDoc
     */
    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @inheritDoc
     */
    public function removeAttribute(string $key): EventInterface
    {
        unset($this->attributes[$key]);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    /**
     * @inheritDoc
     */
    public function setPropagationStopped(bool $flag): EventInterface
    {
        $this->propagationStopped = $flag;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function emit(array $data = []): EventInterface
    {
        $event = $this;
        $listeners = array_map(
            function ($listener) {
                if (is_array($listener)) {
                    $listenerInstance = new $listener['listener']($listener['priority']);
                    if ($listener['catcher'] && method_exists($listenerInstance, 'catch')) {
                        $listenerInstance->catch($listener['catcher']);
                    }
                    return $listenerInstance;
                }
                return $listener;
            }, $this->listeners
        );

        uasort(
            $listeners, function (ListenerInterface $listenerA, ListenerInterface $listenerB) {
                return $listenerB->getPriority() <=> $listenerA->getPriority();
            }
        );
        foreach ($listeners as $listener) {
            /** 
            * @var ListenerInterface $listener 
            */
            try {
                $listenerReturnedValue = $listener->process($event, $data);
            } catch (Throwable $e) {
                /** @var Listener $listener */
                if (method_exists($listener, 'getCatcher') && $listener->getCatcher()) {
                    $listenerReturnedValue = $listener->getCatcher()($event, $e);
                } else {
                    throw $e;
                }
            }
            if ($event->isPropagationStopped() || $listenerReturnedValue === false) {
                break;
            }
            $event = $listenerReturnedValue;
        }
        return $event;
    }

    /**
     * checks if a listener FQCN is valid
     *
     * @param string $listener
     * @return void
     * @throws InvalidListenerException
     */
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
