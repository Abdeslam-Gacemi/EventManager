<?php

namespace Abdeslam\EventManager;

trait MatchableEventCollectionTrait
{
    /** @var EventInterface[] */
    protected $events = [];

    public function getEventsWhere(string $pattern): EventCollection
    {
        $collection = new EventCollection();
        $search = $this->formatPattern($pattern);
        foreach ($this->events as $event) {
            # code...
        }
        return $collection;
    }

    protected function formatPattern(string $pattern): string
    {
        return $pattern;
    }
}
