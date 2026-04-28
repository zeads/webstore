<?php

declare(strict_types=1);

namespace App\States\SalesOrder;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class SalesOrderState extends State
{
    abstract public function label() : string;

    public static function config() : StateConfig
    {
        return parent::config()
            ->default(Pending::class)
            ->allowAllTransitions(Pending::class, Progress::class)
            ->allowAllTransitions(Pending::class, Cancel::class)
            ->allowTransition(Progress::class, Completed::class);
    }

}
