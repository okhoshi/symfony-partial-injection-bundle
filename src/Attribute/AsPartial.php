<?php

declare(strict_types=1);

namespace Okhoshi\PartialInjectionBundle\Attribute;

use Okhoshi\PartialInjectionBundle\PartialInjection;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class AsPartial extends AutoconfigureTag
{
    public const string COMPILER_PASS_TAG = PartialInjection::class;

    public const string LAZY_ARGS_TAG_ATTRIBUTE = 'lazy_args';

    /**
     * @param list<string> $lazyArguments
     */
    public function __construct(string ...$lazyArguments)
    {
        parent::__construct(self::COMPILER_PASS_TAG, [
            self::LAZY_ARGS_TAG_ATTRIBUTE => array_values($lazyArguments),
        ]);
    }
}
