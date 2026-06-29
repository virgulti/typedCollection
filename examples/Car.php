<?php

declare(strict_types=1);

namespace Virgulti\TypedCollection\Examples;

class Car
{
    public string $make;
    public string $model;
    public int $year;

    public function __construct(string $make, string $model, int $year)
    {
        $this->make  = $make;
        $this->model = $model;
        $this->year  = $year;
    }
}
