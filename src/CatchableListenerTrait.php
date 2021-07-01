<?php

/**
 * @author Abdeslam Gacemi <abdobling@gmail.com>
 */

namespace Abdeslam\EventManager;

use Closure;

trait CatchableListenerTrait
{
    /**
     * 
     *
     * @var Closure 
     */
    protected $catcher;

    public function catch(Closure $catcher): void
    {
        $this->catcher = $catcher;
    }

    public function getCatcher(): Closure
    {
        return $this->catcher;
    }
}
