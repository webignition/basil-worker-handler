<?php

declare(strict_types=1);

namespace App\Event\SourceCompile;

use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractSourceCompileEvent extends Event implements SourceCompileEventInterface
{
    public function __construct(private string $source)
    {
    }

    public function getSource(): string
    {
        return $this->source;
    }
}
