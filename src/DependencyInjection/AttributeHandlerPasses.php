<?php
namespace Kaluzki\DerReeder\DependencyInjection;

use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AttributeHandlerPasses implements \IteratorAggregate
{
    private array $attrs = [];

    public function __construct(
        readonly private \Closure $handler,
        readonly private PassPriority|int|null $handlerPriority = PassPriority::REMOVE_BEFORE_UP->value,
        readonly private PassPriority|int|null $configPriority = PassPriority::OPTIMIZE_BEFORE_TOP->value + 1,
    ) {}

    public function getIterator(): \Traversable
    {
        $par = new \ReflectionFunction($this->handler)->getParameters()[0];
        $type = match ($type = (string)$par->getType()) {
            'self' => $par->getDeclaringClass()?->getName(),
            default => $type
        };

        yield new Pass(fn(ContainerBuilder $container) => $container->registerAttributeForAutoconfiguration(
            $type,
            function (ChildDefinition $def, object $attr) {
                $def->addTag($attr::class);
                $this->attrs[$attr::class] = $attr;
            }
        ), $this->configPriority);

        yield new Pass(
            function (ContainerBuilder $container) {
                foreach ($this->attrs as $tagName => $attr) {
                    foreach ($container->findTaggedServiceIds($tagName, true) as $id => $tags) {
                        ($this->handler)(
                            $attr,
                            $container,
                            $id,
                            $container->findDefinition($id),
                            $tagName,
                            $tags
                        );
                    }
                }
            },
            $this->handlerPriority
        );

    }
}
