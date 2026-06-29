<?php

declare(strict_types=1);

namespace Virgulti\TypedCollection;

class IntCollection extends BaseCollection
{
    protected function getType(): string
    {
        return 'int';
    }
}
