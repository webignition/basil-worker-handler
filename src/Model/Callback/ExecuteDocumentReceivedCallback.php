<?php

declare(strict_types=1);

namespace App\Model\Callback;

use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackEntity;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\YamlDocument\Document;

class ExecuteDocumentReceivedCallback extends AbstractCallbackWrapper
{
    private Document $document;

    public function __construct(Document $document)
    {
        $this->document = $document;
        $documentData = $document->parse();
        $documentData = is_array($documentData) ? $documentData : [];

        parent::__construct(CallbackEntity::create(
            CallbackInterface::TYPE_EXECUTE_DOCUMENT_RECEIVED,
            $documentData
        ));
    }

    public function getDocument(): Document
    {
        return $this->document;
    }
}