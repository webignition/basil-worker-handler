<?php

declare(strict_types=1);

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;

abstract class AbstractTestEvent extends Event
{
    public function __construct(private Test $test)
    {
    }

    public function getTest(): Test
    {
        return $this->test;
    }
}
