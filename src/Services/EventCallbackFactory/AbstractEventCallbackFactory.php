<?php

declare(strict_types=1);

namespace App\Services\EventCallbackFactory;

use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\BasilWorker\PersistenceBundle\Services\Factory\CallbackFactory as PersistenceBundleCallbackFactory;

abstract class AbstractEventCallbackFactory implements EventCallbackFactoryInterface
{
    public function __construct(private PersistenceBundleCallbackFactory $persistenceBundleCallbackFactory)
    {
    }

    /**
     * @param CallbackInterface::TYPE_* $type
     * @param array<mixed> $data
     */
    protected function create(string $type, array $data): CallbackInterface
    {
        return $this->persistenceBundleCallbackFactory->create($type, $data);
    }
}
