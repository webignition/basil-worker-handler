<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Services\TestDocumentFactory;
use App\Services\TestDocumentMutator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Dumper;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\BasilWorker\PersistenceBundle\Entity\TestConfiguration;
use webignition\StringPrefixRemover\DefinedStringPrefixRemover;
use webignition\YamlDocumentGenerator\YamlGenerator;

class TestDocumentFactoryTest extends TestCase
{
    private const COMPILER_SOURCE_DIRECTORY = '/app/source';

    private TestDocumentFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new TestDocumentFactory(
            new YamlGenerator(),
            new TestDocumentMutator(
                new Dumper(),
                new DefinedStringPrefixRemover(self::COMPILER_SOURCE_DIRECTORY)
            )
        );
    }

    public function testCreate(): void
    {
        $test = Test::create(
            TestConfiguration::create('chrome', 'http://example.com'),
            '/app/source/test.yml',
            '/app/target/GeneratedTest.php',
            2,
            1
        );

        $document = $this->factory->create($test);

        self::assertSame(
            [
                'type' => 'test',
                'path' => 'test.yml',
                'config' => [
                    'browser' => 'chrome',
                    'url' => 'http://example.com',
                ],
            ],
            $document->parse()
        );
    }
}
