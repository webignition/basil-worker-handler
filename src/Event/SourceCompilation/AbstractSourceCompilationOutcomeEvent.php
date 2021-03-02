<?php

declare(strict_types=1);

namespace App\Event\SourceCompilation;

use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractSourceCompilationOutcomeEvent extends Event implements SourceCompilationOutcomeEventInterface
{
    public function __construct(private string $source)
    {
    }

    public function getSource(): string
    {
        return $this->source;
    }
}
