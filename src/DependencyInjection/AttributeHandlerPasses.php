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
            function (ChildDefinition $def, object $attr, \ReflectionMethod|\ReflectionClass $ref) {
                $def->addTag($tagName = implode('.', [
                    $attr::class,
                    spl_object_id($attr),
                    spl_object_id($ref),
                ]));
                $this->attrs[$tagName] = [$attr, $ref];
            }
        ), $this->configPriority);

        yield new Pass(
            function (ContainerBuilder $container) {
                foreach ($this->attrs as $tagName => [$attr, $ref]) {
                    foreach ($container->findTaggedServiceIds($tagName, true) as $id => $tags) {
                        ($this->handler)(
                            $attr,
                            $container,
                            $ref,
                            $id,
                            $def = $container->findDefinition($id),
                        );
                        $def->clearTag($tagName);
                    }
                }
            },
            $this->handlerPriority
        );

    }
}
