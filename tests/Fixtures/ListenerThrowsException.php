<?php

/**
* @author Abdeslam Gacemi <abdobling@gmail.com>
*/

namespace Tests\Fixtures;

use Abdeslam\EventManager\Contracts\EventInterface;
use Abdeslam\EventManager\Listener;
use Exception;

class ListenerThrowsException extends Listener
{
    public function process(EventInterface $event, array $data = []): EventInterface|bool
    {
        throw new Exception('An exception from the listener');
    }
}