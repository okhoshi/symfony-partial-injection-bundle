<?php

declare(strict_types=1);

namespace Okhoshi\PartialInjectionBundle\DependencyInjection\Compiler;

use Okhoshi\PartialInjectionBundle\Attribute\AsPartial;
use Okhoshi\PartialInjectionBundle\Attribute\Partial;
use Okhoshi\PartialInjectionBundle\PartialInjection;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
final class PartialInjectionPass implements CompilerPassInterface
{
    /**
     * @throws \ReflectionException
     */
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds(AsPartial::COMPILER_PASS_TAG) as $serviceId => $tags) {
            $lazyArgs = $tags[0][AsPartial::LAZY_ARGS_TAG_ATTRIBUTE] ?? [];
            if (!is_array($lazyArgs)) {
                $className = PartialInjection::class;
                throw new \RuntimeException("lazy_args attribute of tag $className must be an array if specified");
            }
            $lazyArgumentNames = array_fill_keys($lazyArgs, false);

            $def = $container->getDefinition($serviceId);
            $defArgs = $def->getArguments();

            $class = $def->getClass();
            if ($class === null) {
                throw new \RuntimeException("Class must be defined for service $serviceId");
            }

            /** @psalm-suppress ArgumentTypeCoercion */
            $refClass = new \ReflectionClass($class);

            $arguments = [];
            foreach ($refClass->getConstructor()?->getParameters() ?? [] as $idx => $parameter) {
                if ($parameter->getAttributes(Partial::class) !== []) {
                    $lazyArgumentNames[$parameter->name] = true;
                    continue;
                }

                if (array_key_exists($parameter->name, $lazyArgumentNames)) {
                    $lazyArgumentNames[$parameter->name] = true;
                    continue;
                }

                if (array_key_exists($idx, $defArgs)) {
                    $arguments[$parameter->name] = $defArgs[$idx];
                }
            }
            if ($lazyArgumentNames === []) {
                throw new \RuntimeException("No lazy arguments are specified for $serviceId");
            }
            if (array_any($lazyArgumentNames, self::isFalse(...))) {
                throw new \RuntimeException(sprintf(
                    'Some lazy arguments (%s) do not correspond to any constructor parameters for %s',
                    implode(', ', array_keys(array_filter(
                        $lazyArgumentNames,
                        self::isFalse(...),
                    ))),
                    $serviceId,
                ));
            }

            $allTags = $def->getTags();
            unset($allTags[PartialInjection::class]);

            $container->register($serviceId, PartialInjection::class)
                ->setArguments([
                    $def->getClass(),
                    array_keys($lazyArgumentNames),
                    $arguments,
                    $def->getMethodCalls(),
                ])
                ->addError($def)
                ->setTags($allTags);

            $svcAliases = array_filter(
                $container->getAliases(),
                self::getAliasCheckFor($serviceId),
                ARRAY_FILTER_USE_BOTH,
            );
            foreach ($svcAliases as $id => $alias) {
                $container->log($this, "Removing alias $id: it's an interface and $alias is a partial service");
                $container->removeAlias($id);
            }
        }
    }

    private static function isFalse(bool $value): bool
    {
        return !$value;
    }

    /**
     * @return \Closure(Alias,string): bool
     */
    private static function getAliasCheckFor(string $id): \Closure
    {
        return static fn (Alias $alias, string $key): bool => interface_exists($key) && $id === (string) $alias;
    }
}
