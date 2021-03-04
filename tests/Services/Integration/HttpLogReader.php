<?php

declare(strict_types=1);

namespace App\Tests\Services\Integration;

use webignition\HttpHistoryContainer\Collection\HttpTransactionCollection;
use webignition\HttpHistoryContainer\Collection\HttpTransactionCollectionInterface;
use webignition\HttpHistoryContainer\Transaction\LoggableTransaction;

class HttpLogReader
{
    public function __construct(private string $path)
    {
    }

    public function getTransactions(): HttpTransactionCollectionInterface
    {
        $content = (string) file_get_contents($this->path);
        $lines = array_filter(explode("\n", $content));

        $transactions = new HttpTransactionCollection();

        foreach ($lines as $line) {
            $loggedTransaction = LoggableTransaction::fromJson($line);
            $transactions->add($loggedTransaction);
        }

        return $transactions;
    }

    public function reset(): void
    {
        file_put_contents($this->path, '');
    }
}
