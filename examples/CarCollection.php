<?php

declare(strict_types=1);

namespace Virgulti\TypedCollection\Examples;

use Virgulti\TypedCollection\BaseCollection;

class CarCollection extends BaseCollection
{
    protected function getType(): string
    {
        return Car::class;
    }
}
