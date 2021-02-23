<?php

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Symplify\ConsoleColorDiff\Bundle\ConsoleColorDiffBundle::class => ['dev' => true, 'test' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],
    Symfony\Bundle\MakerBundle\MakerBundle::class => ['dev' => true],
    DAMA\DoctrineTestBundle\DAMADoctrineTestBundle::class => ['test' => true],
    webignition\BasilWorker\PersistenceBundle\PersistenceBundle::class => ['all' => true],
    webignition\JsonMessageSerializerBundle\JsonMessageSerializerBundle::class => ['all' => true],
    webignition\BasilWorker\StateBundle\StateBundle::class => ['all' => true],
];
