<?php

declare(strict_types=1);

namespace App\Event;

use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\YamlDocument\Document;

interface TestEventInterface
{
    public function getTest(): Test;
    public function getDocument(): Document;
}
