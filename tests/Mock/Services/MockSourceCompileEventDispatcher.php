<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use App\Services\SourceCompileEventDispatcher;
use Mockery\MockInterface;
use webignition\BasilCompilerModels\OutputInterface;

class MockSourceCompileEventDispatcher
{
    private SourceCompileEventDispatcher $mock;

    public function __construct()
    {
        $this->mock = \Mockery::mock(SourceCompileEventDispatcher::class);
    }

    public function getMock(): SourceCompileEventDispatcher
    {
        return $this->mock;
    }

    public function withDispatchCall(string $source, OutputInterface $output): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldReceive('dispatch')
            ->once()
            ->with($source, $output);

        return $this;
    }

    public function withoutDispatchCall(): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldNotReceive('dispatch');

        return $this;
    }
}
