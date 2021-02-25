<?php

declare(strict_types=1);

namespace App\Tests\Services;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;

class EntityRefresher
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * @param array<class-string> $classNames
     */
    public function refreshForEntities(array $classNames): void
    {
        foreach ($classNames as $className) {
            $this->refreshForEntity($className);
        }
    }

    /**
     * @param class-string $className
     */
    public function refreshForEntity(string $className): void
    {
        $repository = $this->entityManager->getRepository($className);
        if ($repository instanceof ObjectRepository) {
            $entities = $repository->findAll();
            foreach ($entities as $entity) {
                $this->entityManager->refresh($entity);
            }
        }
    }
}
