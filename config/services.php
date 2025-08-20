<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services()
        ->defaults()
            ->autowire()
            ->autoconfigure()
    ;

    $services->load('Vuryss\\DoctrineLazyJsonOdm\\', __DIR__.'/../src/');
};
