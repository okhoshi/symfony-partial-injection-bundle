<?php

declare(strict_types=1);

namespace Okhoshi\PartialInjectionBundle\Tests\Unit;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Okhoshi\PartialInjectionBundle\PartialInjection;
use Okhoshi\PartialInjectionBundle\Tests\Fixtures\PartialService;

/**
 * @psalm-suppress PropertyNotSetInConstructor, ArgumentTypeCoercion
 */
class PartialInjectionTest extends TestCase
{
    /**
     * @dataProvider provideTestCreate
     * @param class-string<\Throwable>|null $expectedException
     * @param array<string, array> $methodCalls
     */
    public function testCreate(
        array $lazyArgs,
        array $args,
        ?string $expectedException = null,
        array $methodCalls = [],
        int $expectedCount = 1,
    ): void {
        $pi = new PartialInjection(PartialService::class, $lazyArgs, $args, $methodCalls);

        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        $svc = $pi(value: 'some value');

        Assert::assertInstanceOf(PartialService::class, $svc);
        Assert::assertSame('some value', $svc->value);
        Assert::assertSame($expectedCount, $svc->counter);

        $svc2 = $pi(value: 'some value');

        Assert::assertInstanceOf(PartialService::class, $svc2);
        Assert::assertSame('some value', $svc2->value);
        Assert::assertSame($expectedCount, $svc2->counter);

        Assert::assertNotSame($svc, $svc2);
    }

    public static function provideTestCreate(): iterable
    {
        return [
            'No optional arguments' => [['value'], ['wrong' => null]],
            'With optional arguments' => [['value'], ['wrong' => null, 'optional' => new \stdClass()]],
            'Bad Parameter Name' => [['wrong'], ['wrong' => null], \InvalidArgumentException::class],
            'Unknown argument name' => [['value'], ['unknown' => null, 'wrong' => null], \Error::class],
            'Can\'t shadow Value' => [['value'], ['value' => null, 'wrong' => null]],
            'With one method call' => [['value'], ['wrong' => null], null, [['inc', ['inc']]], 2],
            'With method calls' => [['value'], ['wrong' => null], null, [['inc', ['inc']], ['double', ['dble']]], 4],
            'With method calls 2' => [['value'], ['wrong' => null], null, [['double', ['dble']], ['inc', ['inc']]], 3],
            'With missing method call' => [['value'], ['wrong' => null], \Error::class, [['missing', []]]],
            'With missing arg' => [['value'], ['wrong' => null], \ArgumentCountError::class, [['inc', []]], 1],
        ];
    }
}
