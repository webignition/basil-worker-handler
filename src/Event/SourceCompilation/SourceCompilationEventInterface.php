<?php

declare(strict_types=1);

namespace App\Event\SourceCompilation;

use Psr\EventDispatcher\StoppableEventInterface;

interface SourceCompilationEventInterface extends StoppableEventInterface
{
    public function getSource(): string;
}
