<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services()
        ->defaults()
            ->autowire()
            ->autoconfigure()
    ;

    $services->load('Vuryss\\DoctrineLazyJsonOdm\\', __DIR__.'/../src/');

    $services->alias(
        Vuryss\DoctrineLazyJsonOdm\Serializer\SerializerInterface::class,
        Vuryss\DoctrineLazyJsonOdm\Serializer\Serializer::class,
    );

    $services->alias(
        Vuryss\DoctrineLazyJsonOdm\TypeMapper\TypeMapperInterface::class,
        Vuryss\DoctrineLazyJsonOdm\TypeMapper\TypeMapper::class,
    );
};
