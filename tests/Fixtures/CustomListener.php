<?php

namespace Tests\Fixtures;

use Abdeslam\EventManager\Contracts\EventInterface;
use Abdeslam\EventManager\Listener;

class CustomListener extends Listener
{
    public function process(EventInterface $event, array $data = []): EventInterface
    {
        $event->setAttributes(['message' => 'from custom listener']);
        return $event;
    }
}