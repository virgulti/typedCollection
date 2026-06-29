<?php

declare(strict_types=1);

namespace Virgulti\TypedCollection\Exceptions;

use InvalidArgumentException;

class InvalidItemClassException extends InvalidArgumentException
{
    /**
     * @param mixed $item
     */
    public static function forItem($item, string $expectedType): self
    {
        $actualType = is_object($item) ? get_class($item) : gettype($item);

        return new self(sprintf(
            'Expected item of type "%s", got "%s".',
            $expectedType,
            $actualType
        ));
    }
}
