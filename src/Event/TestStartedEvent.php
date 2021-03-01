<?php

declare(strict_types=1);

namespace App\Event;

use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\YamlDocument\Document;

class TestStartedEvent extends AbstractTestEvent
{
    public function __construct(Test $test, private Document $document)
    {
        parent::__construct($test);
    }

    public function getDocument(): Document
    {
        return $this->document;
    }
}
