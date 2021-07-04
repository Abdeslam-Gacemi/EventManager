<?php

/**
 * @author Abdeslam Gacemi <abdobling@gmail.com>
 */

namespace Abdeslam\EventManager;

use Throwable;
use Abdeslam\EventManager\Contracts\EventInterface;
use Abdeslam\EventManager\Contracts\ListenerInterface;
use Abdeslam\EventManager\Exceptions\InvalidListenerException;

class Listener implements ListenerInterface
{
    use CatchableListenerTrait;

    /**
     * @var integer
     */
    protected $priority = 0;

    /**
     * @var callable|null
     */
    protected $callback;

    public function __construct(int $priority = 0, ?callable $callback = null)
    {
        $this->priority = $priority;
        $this->callback = $callback;
    }

    /**
     * @inheritDoc
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @inheritDoc
     */
    public function setPriority(int $priority): ListenerInterface
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCallback(): ?callable
    {
        return $this->callback;
    }

    /**
     * @inheritDoc
     */
    public function setCallback(callable $callback): ListenerInterface
    {
        $this->callback = $callback;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function process(EventInterface $event, array $data = []): EventInterface|bool
    {
        $callback = $this->getCallback();
        if ($callback !== null) {
            $listenerReturn = call_user_func_array($callback, [$event, $data]);
            if (!$listenerReturn instanceof EventInterface && !is_bool($listenerReturn)) {
                throw new InvalidListenerException('Listener must return an instance of Abdeslam\EventManager\Contracts\EventInterface or a boolean');
            } else {
                return $listenerReturn;
            }
        }
        return $event;
    }
}
