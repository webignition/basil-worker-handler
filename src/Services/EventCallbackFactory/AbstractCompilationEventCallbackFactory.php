<?php

declare(strict_types=1);

namespace App\Services\EventCallbackFactory;

use App\Event\SourceCompilation\SourceCompilationEventInterface;

abstract class AbstractCompilationEventCallbackFactory extends AbstractEventCallbackFactory
{
    /**
     * @param array<mixed> $payload
     *
     * @return array<mixed>
     */
    protected function createPayload(SourceCompilationEventInterface $event, array $payload = []): array
    {
        return array_merge(
            [
                'source' => $event->getSource(),
            ],
            $payload
        );
    }
}
