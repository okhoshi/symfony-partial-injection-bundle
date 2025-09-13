<?php

declare(strict_types=1);

namespace Okhoshi\PartialInjectionBundle;

/**
 * @internal
 * @template T
 */
final readonly class PartialInjection
{
    /**
     * @param class-string<T> $class
     * @param non-empty-list<string> $lazyArguments
     * @param list<array{string, array}> $methodCalls
     */
    public function __construct(
        private string $class,
        private array $lazyArguments,
        private array $arguments,
        private array $methodCalls = [],
    ) {
    }

    /**
     * @return T
     */
    public function __invoke(mixed ...$args): mixed
    {
        $argsKeys = array_keys($args);
        $extraArgs = array_diff($argsKeys, $this->lazyArguments);
        if ($extraArgs !== []) {
            throw new \InvalidArgumentException(sprintf('Unexpected arguments %s', implode(', ', $extraArgs)));
        }
        $missingArgs = array_diff($this->lazyArguments, $argsKeys);
        if ($missingArgs !== []) {
            throw new \InvalidArgumentException(sprintf('Missing arguments %s', implode(', ', $missingArgs)));
        }

        /** @psalm-suppress UnsafeInstantiation */
        $instance = new ($this->class)(...[...$this->arguments, ...$args]);

        foreach ($this->methodCalls as [$method, $args]) {
            $instance->$method(...$args);
        }

        return $instance;
    }
}
