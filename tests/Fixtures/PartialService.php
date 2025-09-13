<?php

declare(strict_types=1);

namespace Okhoshi\PartialInjectionBundle\Tests\Fixtures;

final class PartialService
{
    public int $counter = 1;

    public function __construct(
        public readonly string $value,
        ?object $wrong,
        ?object $optional = null,
    ) {
    }

    public function inc(string $name): void
    {
        if ($name === 'inc') {
            $this->counter++;
        }
    }

    public function double(string $name): void
    {
        if ($name === 'dble') {
            $this->counter *= 2;
        }
    }
}
