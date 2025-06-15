<?php

namespace App\Core\Actions;

abstract class Action
{
    /**
     * Execute the action.
     *
     * @param mixed ...$arguments
     * @return mixed
     */
    abstract public function execute(...$arguments);
}