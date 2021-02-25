<?php

declare(strict_types=1);

namespace App\Event;

use App\Model\Callback\ExecuteDocumentReceivedCallback;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\YamlDocument\Document;

class TestExecuteDocumentReceivedEvent extends AbstractTestEvent implements CallbackEventInterface
{
    public function __construct(
        Test $test,
        private Document $document,
        private ExecuteDocumentReceivedCallback $callback
    ) {
        parent::__construct($test);
    }

    public function getDocument(): Document
    {
        return $this->document;
    }

    public function getCallback(): CallbackInterface
    {
        return $this->callback;
    }
}
