<?php

namespace Abdeslam\EventManager;

use Throwable;
use Abdeslam\EventManager\Contracts\EventInterface;
use Abdeslam\EventManager\Contracts\ListenerInterface;
use Abdeslam\EventManager\Exceptions\InvalidListenerException;

class Listener implements ListenerInterface
{
    use CatchableListenerTrait;

    protected $priority = 0;

    protected $callback;

    public function __construct(int $priority = 0, ?callable $callback = null)
    {
        $this->priority = $priority;
        $this->callback = $callback;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): ListenerInterface
    {
        $this->priority = $priority;
        return $this;
    }

    public function getCallback(): ?callable
    {
        return $this->callback;
    }

    public function setCallback(callable $callback): ListenerInterface
    {
        $this->callback = $callback;
        return $this;
    }

    public function process(EventInterface $event, array $data = []): EventInterface|bool
    {
        $callback = $this->getCallback();
        $listenerReturn = null;
        if ($callback !== null) {
            try {
                $listenerReturn = call_user_func_array($callback, [$event, $data]);
            } catch (Throwable $e) {
                if ($this->getCatcher() !== null) {
                    $listenerReturn = call_user_func($this->getCatcher(), $event, $e);
                } else {
                    throw $e;
                }
            }
            if (!$listenerReturn instanceof EventInterface && !is_bool($listenerReturn)) {
                throw new InvalidListenerException('Listener must return an instance of Abdeslam\EventManager\Contracts\EventInterface or a boolean');
            }
        }
        return $event;
    }
}
