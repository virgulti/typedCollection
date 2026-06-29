<?php

declare(strict_types=1);

namespace Virgulti\TypedCollection;

class StringCollection extends BaseCollection
{
    protected function getType(): string
    {
        return 'string';
    }
}
