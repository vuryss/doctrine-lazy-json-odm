<?php

declare(strict_types=1);

namespace Vuryss\DoctrineJsonOdm\Bundle;

use Doctrine\DBAL\Types\Type;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Vuryss\DoctrineJsonOdm\DependencyInjection\DoctrineJsonOdmExtension;
use Vuryss\DoctrineJsonOdm\Type\JsonDocumentType;

/**
 * Symfony bundle for Doctrine JSON ODM with lazy loading.
 */
class DoctrineJsonOdmBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Register the custom Doctrine type
        if (!Type::hasType(JsonDocumentType::NAME)) {
            Type::addType(JsonDocumentType::NAME, JsonDocumentType::class);
        }
    }

    public function getContainerExtension(): DoctrineJsonOdmExtension
    {
        return new DoctrineJsonOdmExtension();
    }

    public function boot(): void
    {
        parent::boot();

        // Additional initialization if needed
        // The JsonDocumentType configuration is handled by the compiler pass
    }
}
