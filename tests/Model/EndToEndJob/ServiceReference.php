<?php

declare(strict_types=1);

namespace App\Tests\Model\EndToEndJob;

class ServiceReference
{
    public function __construct(private string $id)
    {
    }

    public function getId(): string
    {
        return $this->id;
    }
}
