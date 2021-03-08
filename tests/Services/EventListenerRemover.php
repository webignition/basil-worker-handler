<?php

declare(strict_types=1);

namespace App\Tests\Services;

use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventListenerRemover
{
    public function __construct(
        private ContainerInterface $container,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @param class-string $serviceId
     * @param class-string $eventClass
     * @param string $methodName
     */
    public function removeServiceMethodForEvent(string $serviceId, string $eventClass, string $methodName): void
    {
        $service = $this->container->get($serviceId);
        if (is_object($service)) {
            $callable = [$service, $methodName];
            if (is_callable($callable)) {
                $this->eventDispatcher->removeListener($eventClass, $callable);
            }
        }
    }

    /**
     * @param class-string $serviceId
     * @param array<class-string, string[]> $eventsAndMethods
     */
    public function removeServiceMethodsForEvents(string $serviceId, array $eventsAndMethods): void
    {
        foreach ($eventsAndMethods as $eventClass => $methodNames) {
            foreach ($methodNames as $methodName) {
                $this->removeServiceMethodForEvent($serviceId, $eventClass, $methodName);
            }
        }
    }
}
