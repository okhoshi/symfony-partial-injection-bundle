<?php

declare(strict_types=1);

namespace Okhoshi\PartialInjectionBundle;

use Okhoshi\PartialInjectionBundle\Attribute\AsPartial;
use Okhoshi\PartialInjectionBundle\Attribute\Partial;
use Okhoshi\PartialInjectionBundle\DependencyInjection\Compiler\PartialInjectionPass;
use Symfony\Component\DependencyInjection\Argument\AbstractArgument;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class PartialInjectionBundle extends Bundle
{
    private const string ABSTRACT_TEXT = 'Partial argument, will be set upon creation';

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new PartialInjectionPass(), PassConfig::TYPE_OPTIMIZE, -1);

        /** @psalm-suppress ArgumentTypeCoercion */
        $container->registerAttributeForAutoconfiguration(Partial::class, self::partialConfigurator(...));
        /** @psalm-suppress ArgumentTypeCoercion */
        $container->registerAttributeForAutoconfiguration(AsPartial::class, self::asPartialConfigurator(...));
    }

    private static function partialConfigurator(
        ChildDefinition $definition,
        Partial $attribute,
        \ReflectionParameter $parameter
    ): void {
        $definition->setArgument("\$$parameter->name", new AbstractArgument(self::ABSTRACT_TEXT));
    }

    private static function asPartialConfigurator(
        ChildDefinition $definition,
        AsPartial $attribute,
        \ReflectionClass $class
    ): void {
        $args = $attribute->tags[0][AsPartial::LAZY_ARGS_TAG_ATTRIBUTE] ?? [];
        if (!is_array($args)) {
            return;
        }
        foreach ($args as $argument) {
            $definition->setArgument("\$$argument", new AbstractArgument(self::ABSTRACT_TEXT));
        }
    }
}
