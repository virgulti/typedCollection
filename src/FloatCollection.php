<?php

declare(strict_types=1);

namespace Virgulti\TypedCollection;

class FloatCollection extends BaseCollection
{
    protected function getType(): string
    {
        return 'float';
    }
}
