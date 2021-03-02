<?php

declare(strict_types=1);

namespace App\Event\SourceCompilation;

use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractSourceCompilationEvent extends Event implements SourceCompilationEventInterface
{
    public function __construct(private string $source)
    {
    }

    public function getSource(): string
    {
        return $this->source;
    }
}
